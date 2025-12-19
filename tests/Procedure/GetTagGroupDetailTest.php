<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Model\JsonRpcParams;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Entity\TagGroup;
use Tourze\TagManageBundle\Param\GetTagGroupDetailParam;
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

        $param = new GetTagGroupDetailParam(
            groupId: $groupId,
            includeTags: false
        );
        $result = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $result);
        $resultArray = $result->toArray();
        $this->assertEquals($tagGroup->getId(), $resultArray['id']);
        $this->assertEquals('推荐标签', $resultArray['name']);
        $this->assertArrayHasKey('createTime', $resultArray);
        $this->assertArrayHasKey('updateTime', $resultArray);
        $this->assertArrayHasKey('createdBy', $resultArray);
        $this->assertArrayHasKey('updatedBy', $resultArray);
        $this->assertArrayHasKey('tagCount', $resultArray);
        $this->assertEquals(2, $resultArray['tagCount']);
        $this->assertArrayNotHasKey('tags', $resultArray, '不包含tags时不应该有tags字段');
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

        $param = new GetTagGroupDetailParam(
            groupId: $groupId,
            includeTags: true
        );
        $result = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $result);
        $resultArray = $result->toArray();
        $this->assertEquals($tagGroup->getId(), $resultArray['id']);
        $this->assertEquals('技术标签', $resultArray['name']);
        $this->assertEquals(3, $resultArray['tagCount']);
        $this->assertArrayHasKey('tags', $resultArray);
        $this->assertIsArray($resultArray['tags']);
        $this->assertCount(3, $resultArray['tags']);

        // 检查标签数据结构
        foreach ($resultArray['tags'] as $tag) {
            $this->assertArrayHasKey('id', $tag);
            $this->assertArrayHasKey('name', $tag);
            $this->assertArrayHasKey('valid', $tag);
            $this->assertArrayHasKey('createTime', $tag);
            $this->assertTrue($tag['valid'], '标签应该是有效的');
        }

        // 验证包含所有创建的标签
        $tagNames = array_column($resultArray['tags'], 'name');
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

        $param = new GetTagGroupDetailParam(
            groupId: $groupId,
            includeTags: true
        );
        $result = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $result);
        $resultArray = $result->toArray();
        $this->assertEquals($tagGroup->getId(), $resultArray['id']);
        $this->assertEquals('空标签组', $resultArray['name']);
        $this->assertEquals(0, $resultArray['tagCount']);
        $this->assertArrayHasKey('tags', $resultArray);
        $this->assertIsArray($resultArray['tags']);
        $this->assertEmpty($resultArray['tags']);
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

        $param = new GetTagGroupDetailParam(
            groupId: $groupId,
            includeTags: true
        );
        $result = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $result);
        $resultArray = $result->toArray();
        $this->assertEquals(2, $resultArray['tagCount']); // 应该包含所有标签，不管有效性
        $this->assertArrayHasKey('tags', $resultArray);
        $this->assertCount(2, $resultArray['tags']);

        // 验证包含有效和无效的标签
        $validStatuses = array_column($resultArray['tags'], 'valid');
        $this->assertContains(true, $validStatuses);
        $this->assertContains(false, $validStatuses);
    }

    public function testExecuteWithNonExistentGroup(): void
    {
        $param = new GetTagGroupDetailParam(
            groupId: 'non-existent-group-id',
            includeTags: false
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('标签组不存在');

        $this->procedure->execute($param);
    }

    public function testExecuteCheckDataTypes(): void
    {
        // 验证返回数据的类型
        $tagGroup = $this->createTagGroup('类型测试', '测试数据类型');
        $tag = $this->createTag('测试标签-类型测试', $tagGroup);

        $groupId = $tagGroup->getId();
        self::assertNotNull($groupId);

        $param = new GetTagGroupDetailParam(
            groupId: $groupId,
            includeTags: true
        );
        $result = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $result);
        $resultArray = $result->toArray();
        $this->assertIsString($resultArray['id']);
        $this->assertIsString($resultArray['name']);
        $this->assertIsString($resultArray['createTime']);
        $this->assertIsString($resultArray['updateTime']);
        $this->assertIsInt($resultArray['tagCount']);
        $this->assertIsArray($resultArray['tags']);

        // 检查标签数据类型
        if (($resultArray['tags'] ?? []) !== []) {
            $tag = $resultArray['tags'][0];
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

        $param = new GetTagGroupDetailParam(
            groupId: $groupId,
            includeTags: true
        );
        $result = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $result);
        $resultArray = $result->toArray();
        $this->assertEquals(20, $resultArray['tagCount']);
        $this->assertArrayHasKey('tags', $resultArray);
        $this->assertCount(20, $resultArray['tags']);

        // 验证所有标签都被返回
        $tagNames = array_column($resultArray['tags'], 'name');
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
        $request = new JsonRpcRequest();
        $request->setMethod('tagGroup.detail');

        $duration = $this->procedure->getCacheDuration($request);

        $this->assertEquals(600, $duration); // 10分钟
    }

    public function testGetCacheTags(): void
    {
        $params = new JsonRpcParams([
            'groupId' => '123',
            'includeTags' => false,
        ]);
        $request = new JsonRpcRequest();
        $request->setId('1');
        $request->setMethod('tagGroup.detail');
        $request->setParams($params);

        $tags = iterator_to_array($this->procedure->getCacheTags($request));

        $this->assertContains('tag_group', $tags);
        $this->assertContains('tag_group_detail', $tags);
        $this->assertContains('tag_group_123', $tags);
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
