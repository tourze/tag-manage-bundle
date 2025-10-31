<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Entity\TagGroup;

/**
 * @internal
 */
#[CoversClass(Tag::class)]
final class TagValidationTest extends AbstractEntityTestCase
{
    /**
     * 提供属性及其样本值的 Data Provider.
     *
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $tagGroup = new TagGroup();
        $tagGroup->setName('Test Group');

        yield 'name' => ['name', 'Test Tag'];
        yield 'valid' => ['valid', true];
        yield 'groups' => ['groups', $tagGroup];
    }

    /**
     * 创建被测实体的一个实例.
     */
    protected function createEntity(): object
    {
        return new Tag();
    }
}
