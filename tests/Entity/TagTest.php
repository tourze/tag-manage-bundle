<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TagManageBundle\Entity\Tag;

/**
 * @internal
 */
#[CoversClass(Tag::class)]
final class TagTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Tag();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', 'Test Tag'];
        yield 'valid' => ['valid', true];
    }

    public function testStringable(): void
    {
        $tag = $this->createEntity();
        self::assertInstanceOf(Tag::class, $tag);
        $this->assertSame('', (string) $tag);

        $tag->setName('Test Tag');
        $this->assertSame('', (string) $tag);  // ID为0时返回空字符串
    }

    public function testFluentInterface(): void
    {
        $tag = $this->createEntity();
        self::assertInstanceOf(Tag::class, $tag);
        $name = 'Test Tag';
        $valid = true;

        $tag->setName($name);
        $tag->setValid($valid);

        // 验证设置的值
        $this->assertSame($name, $tag->getName());
        $this->assertSame($valid, $tag->isValid());
    }
}
