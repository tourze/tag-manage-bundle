<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\TagManageBundle\Param\GetTagGroupDetailParam;

/**
 * @internal
 */
#[CoversClass(GetTagGroupDetailParam::class)]
final class GetTagGroupDetailParamTest extends TestCase
{
    public function testParamCanBeConstructed(): void
    {
        $param = new GetTagGroupDetailParam(
            groupId: 'test-group-123',
            includeTags: true
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
        $this->assertSame('test-group-123', $param->groupId);
        $this->assertTrue($param->includeTags);
    }

    public function testParamWithDefaults(): void
    {
        $param = new GetTagGroupDetailParam(groupId: 'test-456');

        $this->assertSame('test-456', $param->groupId);
        $this->assertFalse($param->includeTags);
    }
}
