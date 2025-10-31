<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Repository\TagRepository;

/**
 * @internal
 */
#[CoversClass(TagRepository::class)]
#[RunTestsInSeparateProcesses]
final class TagRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // Repository 测试设置
    }

    protected function getRepository(): TagRepository
    {
        return self::getService(TagRepository::class);
    }

    protected function createNewEntity(): Tag
    {
        $tag = new Tag();
        $tag->setName('Test Tag ' . uniqid());

        return $tag;
    }

    public function testRepositoryCanSaveAndRetrieveEntity(): void
    {
        $repository = $this->getRepository();

        // 创建一个新的Tag实体
        $tag = $this->createNewEntity();

        // 验证保存功能
        $repository->save($tag);

        // 验证实体已被持久化（有ID）
        $this->assertNotNull($tag->getId());
        $this->assertGreaterThan(0, $tag->getId());

        // 通过ID查找实体
        $foundTag = $repository->find($tag->getId());
        $this->assertInstanceOf(Tag::class, $foundTag);
        $this->assertSame($tag->getName(), $foundTag->getName());
    }

    public function testRepositoryCanRemoveEntity(): void
    {
        $repository = $this->getRepository();

        // 创建并保存一个实体
        $tag = $this->createNewEntity();
        $repository->save($tag);
        $tagId = $tag->getId();

        // 验证实体存在
        $this->assertNotNull($repository->find($tagId));

        // 删除实体
        $repository->remove($tag);

        // 验证实体已被删除
        $this->assertNull($repository->find($tagId));
    }

    public function testRepositoryProvidesDoctrineRepositoryFunctionality(): void
    {
        $repository = $this->getRepository();

        // 验证继承自ServiceEntityRepository的基本功能
        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);

        // 验证可以执行基本查询操作
        $allTags = $repository->findAll();
        $this->assertIsArray($allTags);

        // 验证可以按条件查找
        $tag = $this->createNewEntity();
        $repository->save($tag);

        $foundTags = $repository->findBy(['name' => $tag->getName()]);
        $this->assertCount(1, $foundTags);
        $this->assertInstanceOf(Tag::class, $foundTags[0]);
        $this->assertSame($tag->getName(), $foundTags[0]->getName());
    }
}
