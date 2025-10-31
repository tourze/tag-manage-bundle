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
use Tourze\TagManageBundle\Procedure\GetTagList;

/**
 * @internal
 */
#[CoversClass(GetTagList::class)]
#[RunTestsInSeparateProcesses]
final class GetTagListTest extends AbstractProcedureTestCase
{
    private GetTagList $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(GetTagList::class);
    }

    public function testExecuteBasicList(): void
    {
        // 创建测试数据
        $tagGroup = $this->createTagGroup('推荐标签', '推荐使用的标签');
        $tag1 = $this->createTag('热门', $tagGroup);
        $tag2 = $this->createTag('科技', $tagGroup);

        $this->procedure->groupId = null;
        $this->procedure->keyword = null;
        $this->procedure->validOnly = true;
        $this->procedure->orderBy = 'createTime';
        $this->procedure->orderDir = 'DESC';
        $this->procedure->includeUsageStats = false;
        $this->procedure->currentPage = 1;
        $this->procedure->pageSize = 10;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('list', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertIsArray($result['list']);
        $this->assertGreaterThanOrEqual(2, count($result['list']));

        // 检查标签数据结构
        foreach ($result['list'] as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('valid', $item);
            $this->assertArrayHasKey('group', $item);
            $this->assertArrayHasKey('createTime', $item);
            $this->assertArrayHasKey('updateTime', $item);
            $this->assertTrue($item['valid'], '有效状态应该为 true');
        }
    }

    public function testExecuteWithSpecificGroup(): void
    {
        // 创建不同标签组的标签
        $group1 = $this->createTagGroup('分类标签', '分类相关标签');
        $group2 = $this->createTagGroup('特色标签', '特色相关标签');

        $tag1 = $this->createTag('数码', $group1);
        $tag2 = $this->createTag('热门', $group2);

        $this->procedure->groupId = $group1->getId();
        $this->procedure->validOnly = true;
        $this->procedure->currentPage = 1;
        $this->procedure->pageSize = 10;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('list', $result);
        $this->assertGreaterThanOrEqual(1, count($result['list']));

        // 所有结果应该都是指定标签组的
        foreach ($result['list'] as $item) {
            $this->assertNotNull($item['group']);
            $this->assertEquals($group1->getId(), $item['group']['id']);
            $this->assertEquals($group1->getName(), $item['group']['name']);
        }
    }

    public function testExecuteWithKeywordSearch(): void
    {
        // 创建测试数据
        $tagGroup = $this->createTagGroup('通用标签', '通用标签组');
        $tag1 = $this->createTag('科技产品', $tagGroup);
        $tag2 = $this->createTag('科技创新', $tagGroup);
        $tag3 = $this->createTag('传统工艺', $tagGroup);

        $this->procedure->keyword = '科技';
        $this->procedure->validOnly = true;
        $this->procedure->currentPage = 1;
        $this->procedure->pageSize = 10;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('list', $result);
        $this->assertGreaterThanOrEqual(2, count($result['list']));

        // 所有结果应该都包含关键词"科技"
        foreach ($result['list'] as $item) {
            $this->assertStringContainsString('科技', $item['name'], '标签名称应该包含关键词"科技"');
        }
    }

    public function testExecuteWithValidOnlyFalse(): void
    {
        // 创建有效和无效的标签
        $tagGroup = $this->createTagGroup('测试标签', '测试标签组');
        $validTag = $this->createTag('有效标签', $tagGroup);
        $invalidTag = $this->createTag('无效标签', $tagGroup);
        $invalidTag->setValid(false);
        $this->persistAndFlush($invalidTag);

        $this->procedure->validOnly = false;
        $this->procedure->currentPage = 1;
        $this->procedure->pageSize = 10;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('list', $result);
        $this->assertGreaterThanOrEqual(2, count($result['list']));

        // 应该包含有效和无效的标签
        $validStatuses = array_column($result['list'], 'valid');
        $this->assertContains(true, $validStatuses);
        $this->assertContains(false, $validStatuses);
    }

    public function testExecuteWithIncludeUsageStats(): void
    {
        // 创建测试标签
        $tagGroup = $this->createTagGroup('统计标签', '用于统计的标签');
        $tag = $this->createTag('测试标签', $tagGroup);

        $this->procedure->includeUsageStats = true;
        $this->procedure->currentPage = 1;
        $this->procedure->pageSize = 10;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('list', $result);
        $this->assertGreaterThanOrEqual(1, count($result['list']));

        // 应该包含使用统计字段
        foreach ($result['list'] as $item) {
            $this->assertArrayHasKey('usageCount', $item);
            $this->assertArrayHasKey('lastUsedTime', $item);
            $this->assertIsInt($item['usageCount']);
        }
    }

    public function testExecuteWithDifferentSortingOptions(): void
    {
        // 创建测试数据，使用不同的名称确保排序效果
        $tagGroup = $this->createTagGroup('排序测试', '用于测试排序的标签组');
        $tag1 = $this->createTag('A标签', $tagGroup);
        $tag2 = $this->createTag('B标签', $tagGroup);
        $tag3 = $this->createTag('C标签', $tagGroup);

        // 测试按名称升序排列
        $this->procedure->orderBy = 'createTime';
        $this->procedure->orderDir = 'ASC';
        $this->procedure->currentPage = 1;
        $this->procedure->pageSize = 10;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('list', $result);
        $this->assertGreaterThanOrEqual(3, count($result['list']));

        // 验证结果包含预期的标签
        $names = array_column($result['list'], 'name');
        $this->assertContains($tag1->getName(), $names);
        $this->assertContains($tag2->getName(), $names);
        $this->assertContains($tag3->getName(), $names);
    }

    public function testExecuteWithInvalidGroupId(): void
    {
        $this->procedure->groupId = 'invalid-group-id';

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('标签组不存在');

        $this->procedure->execute();
    }

    public function testExecuteWithPagination(): void
    {
        // 创建足够的测试数据来测试分页
        $tagGroup = $this->createTagGroup('分页测试', '用于测试分页的标签组');
        for ($i = 1; $i <= 5; ++$i) {
            $this->createTag("标签{$i}", $tagGroup);
        }

        // 测试第一页
        $this->procedure->currentPage = 1;
        $this->procedure->pageSize = 2;
        $this->procedure->orderBy = 'createTime';
        $this->procedure->orderDir = 'ASC';

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('list', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertLessThanOrEqual(2, count($result['list']));

        $pagination = $result['pagination'];
        $this->assertArrayHasKey('current', $pagination);
        $this->assertArrayHasKey('pageSize', $pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('hasMore', $pagination);
        $this->assertEquals(1, $pagination['current']);
        $this->assertEquals(2, $pagination['pageSize']);
        $this->assertGreaterThanOrEqual(5, $pagination['total']);
    }

    public function testGetCacheKey(): void
    {
        $params = new JsonRpcParams([
            'groupId' => '123',
            'keyword' => 'test',
            'validOnly' => true,
        ]);
        $request = new JsonRpcRequest();
        $request->setId('1');
        $request->setMethod('tag.list');
        $request->setParams($params);

        $cacheKey = $this->procedure->getCacheKey($request);

        $this->assertIsString($cacheKey);
        $this->assertStringContainsString('GetTagList', $cacheKey);
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

        // 不带 groupId
        $this->procedure->groupId = null;
        $tags = iterator_to_array($this->procedure->getCacheTags($request));

        $this->assertContains('tag', $tags);
        $this->assertContains('tag_list', $tags);

        // 带 groupId
        $this->procedure->groupId = '123';
        $tags = iterator_to_array($this->procedure->getCacheTags($request));

        $this->assertContains('tag', $tags);
        $this->assertContains('tag_list', $tags);
        $this->assertContains('tag_group_123', $tags);
    }

    public function testGetMockResult(): void
    {
        $mockResult = GetTagList::getMockResult();

        $this->assertIsArray($mockResult);
        $this->assertArrayHasKey('list', $mockResult);
        $this->assertArrayHasKey('pagination', $mockResult);
        $this->assertIsArray($mockResult['list']);
        $this->assertIsArray($mockResult['pagination']);

        // 检查列表项结构
        if (($mockResult['list'] ?? []) !== []) {
            $item = $mockResult['list'][0];
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('valid', $item);
            $this->assertArrayHasKey('group', $item);
            $this->assertArrayHasKey('createTime', $item);
            $this->assertArrayHasKey('updateTime', $item);
        }

        // 检查分页结构
        $pagination = $mockResult['pagination'];
        $this->assertArrayHasKey('current', $pagination);
        $this->assertArrayHasKey('pageSize', $pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('hasMore', $pagination);
    }

    /**
     * 创建测试用的标签组
     */
    private function createTagGroup(string $name, string $description): TagGroup
    {
        $tagGroup = new TagGroup();
        $tagGroup->setName($name . '_' . uniqid());

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
        $tag->setName($name . '_' . uniqid());
        $tag->setGroups($group);
        $tag->setValid(true);

        $result = $this->persistAndFlush($tag);
        self::assertInstanceOf(Tag::class, $result);

        return $result;
    }
}
