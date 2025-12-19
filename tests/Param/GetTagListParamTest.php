<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\TagManageBundle\Param\GetTagListParam;

/**
 * @internal
 */
#[CoversClass(GetTagListParam::class)]
final class GetTagListParamTest extends TestCase
{
    public function testParamCanBeConstructed(): void
    {
        $param = new GetTagListParam(
            groupId: 'group-123',
            keyword: 'search',
            validOnly: false,
            orderBy: 'name',
            orderDir: 'ASC',
            includeUsageStats: true
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
        $this->assertSame('group-123', $param->groupId);
        $this->assertSame('search', $param->keyword);
        $this->assertFalse($param->validOnly);
        $this->assertSame('name', $param->orderBy);
        $this->assertSame('ASC', $param->orderDir);
        $this->assertTrue($param->includeUsageStats);
    }

    public function testParamWithDefaults(): void
    {
        $param = new GetTagListParam();

        $this->assertNull($param->groupId);
        $this->assertNull($param->keyword);
        $this->assertTrue($param->validOnly);
        $this->assertSame('createTime', $param->orderBy);
        $this->assertSame('DESC', $param->orderDir);
        $this->assertFalse($param->includeUsageStats);
    }
}
