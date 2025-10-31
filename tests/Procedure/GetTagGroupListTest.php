<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Model\JsonRpcParams;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Entity\TagGroup;
use Tourze\TagManageBundle\Procedure\GetTagGroupList;

/**
 * @internal
 */
#[CoversClass(GetTagGroupList::class)]
#[RunTestsInSeparateProcesses]
final class GetTagGroupListTest extends AbstractProcedureTestCase
{
    private GetTagGroupList $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(GetTagGroupList::class);
    }

    public function testExecuteBasicList(): void
    {
        // 创建测试数据
        $group1 = $this->createTagGroup('推荐标签', '推荐使用的标签组');
        $group2 = $this->createTagGroup('热门标签', '热门标签组');

        $this->procedure->page = 1;
        $this->procedure->limit = 20;
        $this->procedure->keyword = null;
        $this->procedure->withTagCount = false;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertIsArray($result['items']);
        $this->assertGreaterThanOrEqual(2, count($result['items']));

        // 检查标签组数据结构
        foreach ($result['items'] as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('createTime', $item);
            $this->assertArrayHasKey('updateTime', $item);
            $this->assertArrayHasKey('createdBy', $item);
            $this->assertArrayHasKey('updatedBy', $item);
            $this->assertArrayNotHasKey('tagCount', $item, '不包含tagCount时不应该有tagCount字段');
        }

        // 检查分页结构
        $pagination = $result['pagination'];
        $this->assertArrayHasKey('page', $pagination);
        $this->assertArrayHasKey('limit', $pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('totalPages', $pagination);
        $this->assertEquals(1, $pagination['page']);
        $this->assertEquals(20, $pagination['limit']);
        $this->assertGreaterThanOrEqual(2, $pagination['total']);
    }

    public function testExecuteWithKeywordSearch(): void
    {
        // 创建测试数据
        $group1 = $this->createTagGroup('技术标签', '技术相关标签组');
        $group2 = $this->createTagGroup('推荐标签', '推荐标签组');
        $group3 = $this->createTagGroup('技术支持', '技术支持标签组');

        $this->procedure->keyword = '技术';
        $this->procedure->page = 1;
        $this->procedure->limit = 20;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertGreaterThanOrEqual(2, count($result['items']));

        // 所有结果应该都包含关键词"技术"
        foreach ($result['items'] as $item) {
            $this->assertStringContainsString('技术', $item['name'], '标签组名称应该包含关键词"技术"');
        }
    }

    public function testExecuteWithTagCount(): void
    {
        // 创建带标签的标签组
        $group1 = $this->createTagGroup('分类标签', '分类标签组');
        $tag1 = $this->createTag('数码-分类标签', $group1);
        $tag2 = $this->createTag('服装-分类标签', $group1);
        $tag3 = $this->createTag('家居-分类标签', $group1);

        $group2 = $this->createTagGroup('空标签组', '没有标签的组');

        $this->procedure->withTagCount = true;
        $this->procedure->page = 1;
        $this->procedure->limit = 20;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertGreaterThanOrEqual(2, count($result['items']));

        // 所有项目都应该包含tagCount字段
        foreach ($result['items'] as $item) {
            $this->assertArrayHasKey('tagCount', $item);
            $this->assertIsInt($item['tagCount']);
            $this->assertGreaterThanOrEqual(0, $item['tagCount']);
        }

        // 找到有标签的组并验证计数
        foreach ($result['items'] as $item) {
            if ('分类标签' === $item['name']) {
                $this->assertEquals(3, $item['tagCount']);
            } elseif ('空标签组' === $item['name']) {
                $this->assertEquals(0, $item['tagCount']);
            }
        }
    }

    public function testExecuteWithPagination(): void
    {
        // 创建足够的测试数据来测试分页
        for ($i = 1; $i <= 8; ++$i) {
            $this->createTagGroup("标签组{$i}", "第{$i}个标签组");
        }

        // 测试第一页
        $this->procedure->page = 1;
        $this->procedure->limit = 3;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertLessThanOrEqual(3, count($result['items']));

        $pagination = $result['pagination'];
        $this->assertEquals(1, $pagination['page']);
        $this->assertEquals(3, $pagination['limit']);
        $this->assertGreaterThanOrEqual(8, $pagination['total']);
        $this->assertGreaterThanOrEqual(3, $pagination['totalPages']);

        // 测试第二页
        $this->procedure->page = 2;
        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(3, count($result['items']));
        $this->assertEquals(2, $result['pagination']['page']);
    }

    public function testExecuteOrderByCreateTime(): void
    {
        // 创建测试数据，按创建时间排序
        $group1 = $this->createTagGroup('第一个组', '第一个创建的组');

        // 稍等一点时间确保创建时间不同
        usleep(1000);

        $group2 = $this->createTagGroup('第二个组', '第二个创建的组');

        usleep(1000);

        $group3 = $this->createTagGroup('第三个组', '第三个创建的组');

        $this->procedure->page = 1;
        $this->procedure->limit = 20;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertGreaterThanOrEqual(3, count($result['items']));

        // 验证按创建时间降序排列（最新的在前面）
        $names = array_column($result['items'], 'name');
        $createTimes = array_column($result['items'], 'createTime');

        // 确保结果包含我们创建的标签组
        $this->assertContains('第一个组', $names);
        $this->assertContains('第二个组', $names);
        $this->assertContains('第三个组', $names);

        // 验证时间格式
        foreach ($createTimes as $time) {
            $this->assertIsString($time);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $time);
        }
    }

    public function testExecuteWithEmptyResult(): void
    {
        // 不创建任何数据，搜索不存在的关键词
        $this->procedure->keyword = '完全不存在的关键词';
        $this->procedure->page = 1;
        $this->procedure->limit = 20;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertEmpty($result['items']);

        $pagination = $result['pagination'];
        $this->assertEquals(0, $pagination['total']);
        $this->assertEquals(0, $pagination['totalPages']);
    }

    public function testExecuteWithLargePage(): void
    {
        // 创建几个标签组
        for ($i = 1; $i <= 3; ++$i) {
            $this->createTagGroup("标签组{$i}", "第{$i}个标签组");
        }

        // 请求一个超大的页码
        $this->procedure->page = 100;
        $this->procedure->limit = 20;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertEmpty($result['items']); // 应该没有数据

        $pagination = $result['pagination'];
        $this->assertEquals(100, $pagination['page']);
        $this->assertEquals(20, $pagination['limit']);
        $this->assertGreaterThan(0, $pagination['total']); // 但总数应该大于0
    }

    public function testExecuteCheckDataTypes(): void
    {
        // 验证返回数据的类型
        $group = $this->createTagGroup('类型测试', '测试数据类型');
        $tag = $this->createTag('测试标签-类型测试-列表', $group);

        $this->procedure->withTagCount = true;
        $this->procedure->page = 1;
        $this->procedure->limit = 20;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertIsArray($result['items']);
        $this->assertIsArray($result['pagination']);

        if (($result['items'] ?? []) !== []) {
            $item = $result['items'][0];
            $this->assertIsString($item['id']);
            $this->assertIsString($item['name']);
            $this->assertIsString($item['createTime']);
            $this->assertIsString($item['updateTime']);
            $this->assertIsInt($item['tagCount']);
        }

        $pagination = $result['pagination'];
        $this->assertIsInt($pagination['page']);
        $this->assertIsInt($pagination['limit']);
        $this->assertIsInt($pagination['total']);
        $this->assertIsInt($pagination['totalPages']);
    }

    public function testGetCacheKey(): void
    {
        $params = new JsonRpcParams([
            'page' => 1,
            'limit' => 20,
            'keyword' => 'test',
            'withTagCount' => true,
        ]);
        $request = new JsonRpcRequest();
        $request->setId('1');
        $request->setMethod('tagGroup.list');
        $request->setParams($params);

        $cacheKey = $this->procedure->getCacheKey($request);

        $this->assertIsString($cacheKey);
        $this->assertStringContainsString('GetTagGroupList', $cacheKey);
    }

    public function testGetCacheDuration(): void
    {
        $request = $this->createMock(JsonRpcRequest::class);

        $duration = $this->procedure->getCacheDuration($request);

        $this->assertEquals(300, $duration); // 5分钟
    }

    public function testGetCacheTags(): void
    {
        $request = $this->createMock(JsonRpcRequest::class);

        $tags = iterator_to_array($this->procedure->getCacheTags($request));

        $this->assertContains('tag_group', $tags);
        $this->assertContains('tag_group_list', $tags);
    }

    public function testGetMockResult(): void
    {
        $mockResult = GetTagGroupList::getMockResult();

        $this->assertIsArray($mockResult);
        $this->assertArrayHasKey('items', $mockResult);
        $this->assertArrayHasKey('pagination', $mockResult);
        $this->assertIsArray($mockResult['items']);
        $this->assertIsArray($mockResult['pagination']);

        // 检查列表项结构
        if (($mockResult['items'] ?? []) !== []) {
            $item = $mockResult['items'][0];
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('createTime', $item);
            $this->assertArrayHasKey('updateTime', $item);
            $this->assertArrayHasKey('createdBy', $item);
            $this->assertArrayHasKey('updatedBy', $item);
            $this->assertArrayHasKey('tagCount', $item);
        }

        // 检查分页结构
        $pagination = $mockResult['pagination'];
        $this->assertArrayHasKey('page', $pagination);
        $this->assertArrayHasKey('limit', $pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('totalPages', $pagination);
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
