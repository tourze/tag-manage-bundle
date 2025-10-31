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
final class TagGroupTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new TagGroup();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', 'Test Group'];
    }

    public function testStringable(): void
    {
        // 测试空ID时返回空字符串
        $tagGroup = $this->createEntity();
        self::assertInstanceOf(TagGroup::class, $tagGroup);
        $this->assertSame('', (string) $tagGroup);

        // 测试有名称时，但ID为null时仍返回空字符串
        $tagGroup->setName('Test Group');
        $this->assertSame('', (string) $tagGroup);  // ID为null时返回空字符串
    }

    public function testFluentInterface(): void
    {
        $tagGroup = $this->createEntity();
        self::assertInstanceOf(TagGroup::class, $tagGroup);
        $name = 'Test Group';

        $tagGroup->setName($name);

        // 验证设置的值
        $this->assertSame($name, $tagGroup->getName());
    }
}
