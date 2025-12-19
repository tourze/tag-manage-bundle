<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\TagManageBundle\Param\GetTagGroupListParam;

/**
 * @internal
 */
#[CoversClass(GetTagGroupListParam::class)]
final class GetTagGroupListParamTest extends TestCase
{
    public function testParamCanBeConstructed(): void
    {
        $param = new GetTagGroupListParam(
            page: 2,
            limit: 50,
            keyword: 'test',
            withTagCount: true
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
        $this->assertSame(2, $param->page);
        $this->assertSame(50, $param->limit);
        $this->assertSame('test', $param->keyword);
        $this->assertTrue($param->withTagCount);
    }

    public function testParamWithDefaults(): void
    {
        $param = new GetTagGroupListParam();

        $this->assertSame(1, $param->page);
        $this->assertSame(20, $param->limit);
        $this->assertNull($param->keyword);
        $this->assertFalse($param->withTagCount);
    }
}
