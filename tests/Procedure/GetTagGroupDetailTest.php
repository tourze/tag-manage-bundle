<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Model\JsonRpcParams;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Entity\TagGroup;
use Tourze\TagManageBundle\Procedure\GetTagGroupDetail;

/**
 * @internal
 */
#[CoversClass(GetTagGroupDetail::class)]
#[RunTestsInSeparateProcesses]
final class GetTagGroupDetailTest extends AbstractProcedureTestCase
{
    private GetTagGroupDetail $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(GetTagGroupDetail::class);
    }

    public function testExecuteBasicDetail(): void
    {
        // 创建测试数据
        $tagGroup = $this->createTagGroup('推荐标签', '推荐使用的标签组');
        $tag1 = $this->createTag('热门-推荐标签', $tagGroup);
        $tag2 = $this->createTag('推荐-推荐标签', $tagGroup);

        $groupId = $tagGroup->getId();
        self::assertNotNull($groupId);
        $this->procedure->groupId = $groupId;
        $this->procedure->includeTags = false;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertEquals($tagGroup->getId(), $result['id']);
        $this->assertEquals('推荐标签', $result['name']);
        $this->assertArrayHasKey('createTime', $result);
        $this->assertArrayHasKey('updateTime', $result);
        $this->assertArrayHasKey('createdBy', $result);
        $this->assertArrayHasKey('updatedBy', $result);
        $this->assertArrayHasKey('tagCount', $result);
        $this->assertEquals(2, $result['tagCount']);
        $this->assertArrayNotHasKey('tags', $result, '不包含tags时不应该有tags字段');
    }

    public function testExecuteWithIncludeTags(): void
    {
        // 创建测试数据
        $tagGroup = $this->createTagGroup('技术标签', '技术相关标签组');
        $tag1 = $this->createTag('PHP-技术标签', $tagGroup);
        $tag2 = $this->createTag('JavaScript-技术标签', $tagGroup);
        $tag3 = $this->createTag('Python-技术标签', $tagGroup);

        $groupId = $tagGroup->getId();
        self::assertNotNull($groupId);
        $this->procedure->groupId = $groupId;
        $this->procedure->includeTags = true;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertEquals($tagGroup->getId(), $result['id']);
        $this->assertEquals('技术标签', $result['name']);
        $this->assertEquals(3, $result['tagCount']);
        $this->assertArrayHasKey('tags', $result);
        $this->assertIsArray($result['tags']);
        $this->assertCount(3, $result['tags']);

        // 检查标签数据结构
        foreach ($result['tags'] as $tag) {
            $this->assertArrayHasKey('id', $tag);
            $this->assertArrayHasKey('name', $tag);
            $this->assertArrayHasKey('valid', $tag);
            $this->assertArrayHasKey('createTime', $tag);
            $this->assertTrue($tag['valid'], '标签应该是有效的');
        }

        // 验证包含所有创建的标签
        $tagNames = array_column($result['tags'], 'name');
        $this->assertContains('PHP-技术标签', $tagNames);
        $this->assertContains('JavaScript-技术标签', $tagNames);
        $this->assertContains('Python-技术标签', $tagNames);
    }

    public function testExecuteWithEmptyTagGroup(): void
    {
        // 创建没有标签的标签组
        $tagGroup = $this->createTagGroup('空标签组', '没有标签的组');

        $groupId = $tagGroup->getId();
        self::assertNotNull($groupId);
        $this->procedure->groupId = $groupId;
        $this->procedure->includeTags = true;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertEquals($tagGroup->getId(), $result['id']);
        $this->assertEquals('空标签组', $result['name']);
        $this->assertEquals(0, $result['tagCount']);
        $this->assertArrayHasKey('tags', $result);
        $this->assertIsArray($result['tags']);
        $this->assertEmpty($result['tags']);
    }

    public function testExecuteWithMixedValidTags(): void
    {
        // 创建带有有效和无效标签的标签组
        $tagGroup = $this->createTagGroup('混合标签', '包含有效和无效标签的组');
        $validTag = $this->createTag('有效标签-混合标签', $tagGroup);
        $invalidTag = $this->createTag('无效标签-混合标签', $tagGroup);
        $invalidTag->setValid(false);
        $this->persistAndFlush($invalidTag);

        $groupId = $tagGroup->getId();
        self::assertNotNull($groupId);
        $this->procedure->groupId = $groupId;
        $this->procedure->includeTags = true;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertEquals(2, $result['tagCount']); // 应该包含所有标签，不管有效性
        $this->assertArrayHasKey('tags', $result);
        $this->assertCount(2, $result['tags']);

        // 验证包含有效和无效的标签
        $validStatuses = array_column($result['tags'], 'valid');
        $this->assertContains(true, $validStatuses);
        $this->assertContains(false, $validStatuses);
    }

    public function testExecuteWithNonExistentGroup(): void
    {
        $this->procedure->groupId = 'non-existent-group-id';

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('标签组不存在');

        $this->procedure->execute();
    }

    public function testExecuteCheckDataTypes(): void
    {
        // 验证返回数据的类型
        $tagGroup = $this->createTagGroup('类型测试', '测试数据类型');
        $tag = $this->createTag('测试标签-类型测试', $tagGroup);

        $groupId = $tagGroup->getId();
        self::assertNotNull($groupId);
        $this->procedure->groupId = $groupId;
        $this->procedure->includeTags = true;

        $result = $this->procedure->execute();

        $this->assertIsString($result['id']);
        $this->assertIsString($result['name']);
        $this->assertIsString($result['createTime']);
        $this->assertIsString($result['updateTime']);
        $this->assertIsInt($result['tagCount']);
        $this->assertIsArray($result['tags']);

        // 检查标签数据类型
        if (($result['tags'] ?? []) !== []) {
            $tag = $result['tags'][0];
            $this->assertIsInt($tag['id']); // Tag ID 是整数类型
            $this->assertIsString($tag['name']);
            $this->assertIsBool($tag['valid']);
            $this->assertIsString($tag['createTime']);
        }
    }

    public function testExecuteWithLargeNumberOfTags(): void
    {
        // 测试包含大量标签的情况
        $tagGroup = $this->createTagGroup('大量标签', '包含大量标签的组');

        // 创建多个标签
        for ($i = 1; $i <= 20; ++$i) {
            $this->createTag("标签{$i}-大量标签", $tagGroup);
        }

        $groupId = $tagGroup->getId();
        self::assertNotNull($groupId);
        $this->procedure->groupId = $groupId;
        $this->procedure->includeTags = true;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertEquals(20, $result['tagCount']);
        $this->assertArrayHasKey('tags', $result);
        $this->assertCount(20, $result['tags']);

        // 验证所有标签都被返回
        $tagNames = array_column($result['tags'], 'name');
        for ($i = 1; $i <= 20; ++$i) {
            $this->assertContains("标签{$i}-大量标签", $tagNames);
        }
    }

    public function testGetCacheKey(): void
    {
        $params = new JsonRpcParams([
            'groupId' => '123',
            'includeTags' => true,
        ]);
        $request = new JsonRpcRequest();
        $request->setId('1');
        $request->setMethod('tagGroup.detail');
        $request->setParams($params);

        $cacheKey = $this->procedure->getCacheKey($request);

        $this->assertIsString($cacheKey);
        $this->assertStringContainsString('GetTagGroupDetail', $cacheKey);
    }

    public function testGetCacheDuration(): void
    {
        $request = $this->createMock(JsonRpcRequest::class);

        $duration = $this->procedure->getCacheDuration($request);

        $this->assertEquals(600, $duration); // 10分钟
    }

    public function testGetCacheTags(): void
    {
        $request = $this->createMock(JsonRpcRequest::class);
        $this->procedure->groupId = '123';

        $tags = iterator_to_array($this->procedure->getCacheTags($request));

        $this->assertContains('tag_group', $tags);
        $this->assertContains('tag_group_detail', $tags);
        $this->assertContains('tag_group_123', $tags);
    }

    public function testGetMockResult(): void
    {
        $mockResult = GetTagGroupDetail::getMockResult();

        $this->assertIsArray($mockResult);
        $this->assertArrayHasKey('id', $mockResult);
        $this->assertArrayHasKey('name', $mockResult);
        $this->assertArrayHasKey('createTime', $mockResult);
        $this->assertArrayHasKey('updateTime', $mockResult);
        $this->assertArrayHasKey('createdBy', $mockResult);
        $this->assertArrayHasKey('updatedBy', $mockResult);
        $this->assertArrayHasKey('tagCount', $mockResult);
        $this->assertArrayHasKey('tags', $mockResult);
        $this->assertIsArray($mockResult['tags']);

        // 检查标签项结构
        if (($mockResult['tags'] ?? []) !== []) {
            $tag = $mockResult['tags'][0];
            $this->assertArrayHasKey('id', $tag);
            $this->assertArrayHasKey('name', $tag);
            $this->assertArrayHasKey('valid', $tag);
            $this->assertArrayHasKey('createTime', $tag);
        }
    }

    /**
     * 创建测试用的标签组
     */
    private function createTagGroup(string $name, string $description): TagGroup
    {
        $tagGroup = new TagGroup();
        $tagGroup->setName($name);

        $result = $this->persistAndFlush($tagGroup);
        self::assertInstanceOf(TagGroup::class, $result);

        return $result;
    }

    /**
     * 创建测试用的标签
     */
    private function createTag(string $name, TagGroup $group): Tag
    {
        $tag = new Tag();
        $tag->setName($name);
        $tag->setGroups($group);
        $tag->setValid(true); // 确保标签是有效的

        // 确保双向关联关系
        $group->addTag($tag);

        $result = $this->persistAndFlush($tag);
        self::assertInstanceOf(Tag::class, $result);

        return $result;
    }
}
