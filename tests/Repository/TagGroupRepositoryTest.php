<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TagManageBundle\Entity\TagGroup;
use Tourze\TagManageBundle\Repository\TagGroupRepository;

/**
 * @internal
 */
#[CoversClass(TagGroupRepository::class)]
#[RunTestsInSeparateProcesses]
final class TagGroupRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // Repository 测试设置
    }

    protected function getRepository(): TagGroupRepository
    {
        return self::getService(TagGroupRepository::class);
    }

    protected function createNewEntity(): TagGroup
    {
        $tagGroup = new TagGroup();
        $tagGroup->setName('Test Tag Group ' . uniqid());

        return $tagGroup;
    }

    public function testRepositoryCanSaveAndRetrieveEntity(): void
    {
        $repository = $this->getRepository();

        // 创建一个新的TagGroup实体
        $tagGroup = $this->createNewEntity();

        // 验证保存功能
        $repository->save($tagGroup);

        // 验证实体已被持久化（有ID）
        $this->assertNotNull($tagGroup->getId());

        // 通过ID查找实体
        $foundTagGroup = $repository->find($tagGroup->getId());
        $this->assertInstanceOf(TagGroup::class, $foundTagGroup);
        $this->assertSame($tagGroup->getName(), $foundTagGroup->getName());
    }

    public function testRepositoryCanRemoveEntity(): void
    {
        $repository = $this->getRepository();

        // 创建并保存一个实体
        $tagGroup = $this->createNewEntity();
        $repository->save($tagGroup);
        $tagGroupId = $tagGroup->getId();

        // 验证实体存在
        $this->assertNotNull($repository->find($tagGroupId));

        // 删除实体
        $repository->remove($tagGroup);

        // 验证实体已被删除
        $this->assertNull($repository->find($tagGroupId));
    }

    public function testRepositoryProvidesDoctrineRepositoryFunctionality(): void
    {
        $repository = $this->getRepository();

        // 验证继承自ServiceEntityRepository的基本功能
        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);

        // 验证可以执行基本查询操作
        $allTagGroups = $repository->findAll();
        $this->assertIsArray($allTagGroups);

        // 验证可以按条件查找
        $tagGroup = $this->createNewEntity();
        $repository->save($tagGroup);

        $foundTagGroups = $repository->findBy(['name' => $tagGroup->getName()]);
        $this->assertCount(1, $foundTagGroups);
        $this->assertInstanceOf(TagGroup::class, $foundTagGroups[0]);
        $this->assertSame($tagGroup->getName(), $foundTagGroups[0]->getName());
    }
}
