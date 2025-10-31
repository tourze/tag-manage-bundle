<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TagManageBundle\Entity\TagGroup;

/**
 * @internal
 */
#[CoversClass(TagGroup::class)]
final class TagGroupValidationTest extends AbstractEntityTestCase
{
    /**
     * 提供属性及其样本值的 Data Provider.
     *
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'id' => ['id', '123456789012345678'];
        yield 'name' => ['name', 'Test Group'];
    }

    /**
     * 创建被测实体的一个实例.
     */
    protected function createEntity(): object
    {
        return new TagGroup();
    }
}
