<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\TagManageBundle\Param\SearchTagsParam;

/**
 * @internal
 */
#[CoversClass(SearchTagsParam::class)]
final class SearchTagsParamTest extends TestCase
{
    public function testParamCanBeConstructed(): void
    {
        $param = new SearchTagsParam(
            keyword: 'test',
            limit: 20,
            validOnly: false,
            groupId: 'group-123',
            orderByUsage: true
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
        $this->assertSame('test', $param->keyword);
        $this->assertSame(20, $param->limit);
        $this->assertFalse($param->validOnly);
        $this->assertSame('group-123', $param->groupId);
        $this->assertTrue($param->orderByUsage);
    }

    public function testParamWithDefaults(): void
    {
        $param = new SearchTagsParam(keyword: 'search');

        $this->assertSame('search', $param->keyword);
        $this->assertSame(10, $param->limit);
        $this->assertTrue($param->validOnly);
        $this->assertNull($param->groupId);
        $this->assertFalse($param->orderByUsage);
    }
}
