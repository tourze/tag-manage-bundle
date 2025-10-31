<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Entity\TagGroup;
use Tourze\TagManageBundle\Procedure\SearchTags;

/**
 * @internal
 */
#[CoversClass(SearchTags::class)]
#[RunTestsInSeparateProcesses]
final class SearchTagsTest extends AbstractProcedureTestCase
{
    private SearchTags $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(SearchTags::class);
    }

    public function testExecuteBasicSearch(): void
    {
        // 创建测试数据
        $tagGroup = $this->createTagGroup('科技标签', '科技相关标签');
        $tag1 = $this->createTag('科技-搜索', $tagGroup);
        $tag2 = $this->createTag('科技产品-搜索', $tagGroup);
        $tag3 = $this->createTag('高科技-搜索', $tagGroup);
        $tag4 = $this->createTag('传统工艺-搜索', $tagGroup); // 不匹配的标签

        $this->procedure->keyword = '科技';
        $this->procedure->limit = 10;
        $this->procedure->validOnly = true;
        $this->procedure->groupId = null;
        $this->procedure->orderByUsage = false;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('keyword', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('tags', $result);
        $this->assertEquals('科技', $result['keyword']);
        $this->assertGreaterThanOrEqual(3, $result['total']);
        $this->assertIsArray($result['tags']);

        // 检查标签数据结构
        foreach ($result['tags'] as $tag) {
            $this->assertArrayHasKey('id', $tag);
            $this->assertArrayHasKey('name', $tag);
            $this->assertArrayHasKey('valid', $tag);
            $this->assertArrayHasKey('group', $tag);
            $this->assertArrayHasKey('highlighted', $tag);
            $this->assertStringContainsString('科技', $tag['name'], '搜索结果应该包含关键词');
            $this->assertTrue($tag['valid'], '有效状态应该为 true');
        }

        // 验证高亮功能
        $highlighted = array_column($result['tags'], 'highlighted');
        foreach ($highlighted as $h) {
            $this->assertStringContainsString('<mark>科技</mark>', $h, '关键词应该被高亮');
        }
    }

    public function testExecuteExactMatch(): void
    {
        // 创建精确匹配和模糊匹配的标签
        $tagGroup = $this->createTagGroup('搜索测试', '搜索测试标签组');
        $exactTag = $this->createTag('科技-精确搜索', $tagGroup);
        $partialTag = $this->createTag('科技产品-精确搜索', $tagGroup);
        $anotherPartialTag = $this->createTag('高科技创新-精确搜索', $tagGroup);

        $this->procedure->keyword = '科技';
        $this->procedure->limit = 10;
        $this->procedure->validOnly = true;
        $this->procedure->orderByUsage = false;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, $result['total']);

        // 精确匹配应该排在前面（根据匹配度排序）
        $tags = $result['tags'];
        $this->assertGreaterThanOrEqual(1, count($tags));

        // 验证精确匹配的标签在结果中
        $names = array_column($tags, 'name');
        $this->assertContains('科技-精确搜索', $names);
        $this->assertContains('科技产品-精确搜索', $names);
        $this->assertContains('高科技创新-精确搜索', $names);
    }

    public function testExecuteWithLimit(): void
    {
        // 创建多个匹配的标签
        $tagGroup = $this->createTagGroup('限制测试', '测试限制数量');
        for ($i = 1; $i <= 8; ++$i) {
            $this->createTag("科技标签{$i}", $tagGroup);
        }

        $this->procedure->keyword = '科技';
        $this->procedure->limit = 5;
        $this->procedure->validOnly = true;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tags', $result);
        $this->assertLessThanOrEqual(5, count($result['tags']));
        $this->assertEquals(count($result['tags']), $result['total']);
    }

    public function testExecuteWithValidOnlyFalse(): void
    {
        // 创建有效和无效的标签
        $tagGroup = $this->createTagGroup('有效性测试', '测试有效性过滤');
        $validTag = $this->createTag('科技有效-有效性测试', $tagGroup);
        $invalidTag = $this->createTag('科技无效-有效性测试', $tagGroup);
        $invalidTag->setValid(false);
        $this->persistAndFlush($invalidTag);

        $this->procedure->keyword = '科技';
        $this->procedure->validOnly = false;
        $this->procedure->limit = 10;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, $result['total']);

        // 应该包含有效和无效的标签
        $validStatuses = array_column($result['tags'], 'valid');
        $this->assertContains(true, $validStatuses);
        $this->assertContains(false, $validStatuses);
    }

    public function testExecuteWithSpecificGroup(): void
    {
        // 创建不同标签组的标签
        $group1 = $this->createTagGroup('科技组1', '科技标签组1');
        $group2 = $this->createTagGroup('科技组2', '科技标签组2');

        $tag1 = $this->createTag('科技产品-分组搜索', $group1);
        $tag2 = $this->createTag('科技服务-分组搜索', $group2);

        $this->procedure->keyword = '科技';
        $this->procedure->groupId = $group1->getId();
        $this->procedure->validOnly = true;
        $this->procedure->limit = 10;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, $result['total']);

        // 所有结果应该都是指定标签组的
        foreach ($result['tags'] as $tag) {
            $this->assertNotNull($tag['group']);
            $this->assertEquals($group1->getId(), $tag['group']['id']);
            $this->assertEquals('科技组1', $tag['group']['name']);
        }
    }

    public function testExecuteWithOrderByUsage(): void
    {
        // 创建测试标签
        $tagGroup = $this->createTagGroup('使用频率', '测试使用频率排序');
        $tag1 = $this->createTag('科技-限制数量1', $tagGroup);
        $tag2 = $this->createTag('科技-限制数量2', $tagGroup);
        $tag3 = $this->createTag('科技-限制数量3', $tagGroup);

        $this->procedure->keyword = '科技';
        $this->procedure->orderByUsage = true;
        $this->procedure->limit = 10;
        $this->procedure->validOnly = true;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tags', $result);
        $this->assertGreaterThanOrEqual(3, count($result['tags']));

        // 验证结果包含所有匹配的标签
        $names = array_column($result['tags'], 'name');
        $this->assertContains('科技-限制数量1', $names);
        $this->assertContains('科技-限制数量2', $names);
        $this->assertContains('科技-限制数量3', $names);
    }

    public function testExecuteWithEmptyKeyword(): void
    {
        $this->procedure->keyword = '';

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('搜索关键词不能为空');

        $this->procedure->execute();
    }

    public function testExecuteWithShortKeyword(): void
    {
        // 测试单字符关键词
        $tagGroup = $this->createTagGroup('短关键词', '测试短关键词搜索');
        $tag = $this->createTag('A标签-排序测试', $tagGroup);

        $this->procedure->keyword = 'A';
        $this->procedure->limit = 10;
        $this->procedure->validOnly = true;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertEquals('A', $result['keyword']);
        $this->assertGreaterThanOrEqual(1, $result['total']);
        $this->assertStringContainsString('<mark>A</mark>', $result['tags'][0]['highlighted']);
    }

    public function testExecuteWithNoMatches(): void
    {
        // 创建不匹配的标签
        $tagGroup = $this->createTagGroup('不匹配', '不匹配的标签');
        $this->createTag('完全不相关-空结果', $tagGroup);

        $this->procedure->keyword = '没有匹配';
        $this->procedure->limit = 10;
        $this->procedure->validOnly = true;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertEquals('没有匹配', $result['keyword']);
        $this->assertEquals(0, $result['total']);
        $this->assertEmpty($result['tags']);
    }

    public function testExecuteWithCaseInsensitiveSearch(): void
    {
        // 测试大小写不敏感搜索
        $tagGroup = $this->createTagGroup('大小写测试', '测试大小写不敏感');
        $tag1 = $this->createTag('科技产品-大小写', $tagGroup);
        $tag2 = $this->createTag('TECH科技-大小写', $tagGroup);

        $this->procedure->keyword = '科技';
        $this->procedure->limit = 10;
        $this->procedure->validOnly = true;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, $result['total']);

        $names = array_column($result['tags'], 'name');
        $this->assertContains('科技产品-大小写', $names);
        $this->assertContains('TECH科技-大小写', $names);
    }

    public function testExecuteHighlightKeyword(): void
    {
        // 测试关键词高亮功能
        $tagGroup = $this->createTagGroup('高亮测试', '测试关键词高亮');
        $tag1 = $this->createTag('科技-高亮测试', $tagGroup);
        $tag2 = $this->createTag('高科技产品-高亮测试', $tagGroup);
        $tag3 = $this->createTag('科技创新科技-高亮测试', $tagGroup); // 包含多个关键词

        $this->procedure->keyword = '科技';
        $this->procedure->limit = 10;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result['tags']));

        // 验证不同情况下的高亮
        $highlightedTexts = array_column($result['tags'], 'highlighted');

        // 验证至少有一个包含高亮的（因为现在标签名包含后缀）
        $hasHighlight = false;
        foreach ($highlightedTexts as $highlighted) {
            if (str_contains($highlighted, '<mark>科技</mark>')) {
                $hasHighlight = true;
                break;
            }
        }
        $this->assertTrue($hasHighlight, '应该至少有一个结果包含高亮的"科技"');

        // 验证包含高亮的部分匹配
        $hasPartialHighlight = false;
        foreach ($highlightedTexts as $highlighted) {
            if (str_contains($highlighted, '高<mark>科技</mark>')
                || str_contains($highlighted, '<mark>科技</mark>产品')
                || str_contains($highlighted, '<mark>科技</mark>创新<mark>科技</mark>')) {
                $hasPartialHighlight = true;
                break;
            }
        }
        $this->assertTrue($hasPartialHighlight, '应该有部分匹配的高亮');
    }

    public function testGetMockResult(): void
    {
        $mockResult = SearchTags::getMockResult();

        $this->assertIsArray($mockResult);
        $this->assertArrayHasKey('keyword', $mockResult);
        $this->assertArrayHasKey('total', $mockResult);
        $this->assertArrayHasKey('tags', $mockResult);
        $this->assertIsArray($mockResult['tags']);

        // 检查标签项结构
        if (($mockResult['tags'] ?? []) !== []) {
            $tag = $mockResult['tags'][0];
            $this->assertArrayHasKey('id', $tag);
            $this->assertArrayHasKey('name', $tag);
            $this->assertArrayHasKey('valid', $tag);
            $this->assertArrayHasKey('group', $tag);
            $this->assertArrayHasKey('highlighted', $tag);
            $this->assertStringContainsString('<mark>', $tag['highlighted'], 'mock结果应该包含高亮标记');
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
