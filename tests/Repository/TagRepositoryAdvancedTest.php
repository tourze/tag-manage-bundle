<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Entity\TagGroup;
use Tourze\TagManageBundle\Repository\TagRepository;

/**
 * @internal
 */
#[CoversClass(TagRepository::class)]
#[RunTestsInSeparateProcesses]
final class TagRepositoryAdvancedTest extends AbstractRepositoryTestCase
{
    private TagRepository $repository;

    public function testFindByValidStatus(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $validTag = new Tag();
        $validTag->setName('Valid Tag');
        $validTag->setValid(true);
        $this->persistAndFlush($validTag);

        $invalidTag = new Tag();
        $invalidTag->setName('Invalid Tag');
        $invalidTag->setValid(false);
        $this->persistAndFlush($invalidTag);

        // 只查询我们创建的标签
        $validTags = $this->repository->findBy([
            'valid' => true,
            'id' => [$validTag->getId(), $invalidTag->getId()],
        ]);
        $invalidTags = $this->repository->findBy([
            'valid' => false,
            'id' => [$validTag->getId(), $invalidTag->getId()],
        ]);

        $this->assertCount(1, $validTags);
        $this->assertCount(1, $invalidTags);
        $this->assertEquals('Valid Tag', $validTags[0]->getName());
        $this->assertEquals('Invalid Tag', $invalidTags[0]->getName());
    }

    public function testFindByTagGroup(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $group1 = new TagGroup();
        $group1->setName('Group 1');
        $group1 = $this->persistAndFlush($group1);
        self::assertInstanceOf(TagGroup::class, $group1);

        $group2 = new TagGroup();
        $group2->setName('Group 2');
        $group2 = $this->persistAndFlush($group2);
        self::assertInstanceOf(TagGroup::class, $group2);

        $tag1 = new Tag();
        $tag1->setName('Tag 1');
        $tag1->setValid(true);
        $tag1->setGroups($group1);
        $this->persistAndFlush($tag1);

        $tag2 = new Tag();
        $tag2->setName('Tag 2');
        $tag2->setValid(true);
        $tag2->setGroups($group1);
        $this->persistAndFlush($tag2);

        $tag3 = new Tag();
        $tag3->setName('Tag 3');
        $tag3->setValid(true);
        $tag3->setGroups($group2);
        $this->persistAndFlush($tag3);

        $group1Tags = $this->repository->findBy(['groups' => $group1]);
        $group2Tags = $this->repository->findBy(['groups' => $group2]);

        $this->assertCount(2, $group1Tags);
        $this->assertCount(1, $group2Tags);
    }

    public function testFindByPartialName(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $tag1 = new Tag();
        $tag1->setName('热门推荐');
        $tag1->setValid(true);
        $this->persistAndFlush($tag1);

        $tag2 = new Tag();
        $tag2->setName('推荐文章');
        $tag2->setValid(true);
        $this->persistAndFlush($tag2);

        $tag3 = new Tag();
        $tag3->setName('最新资讯');
        $tag3->setValid(true);
        $this->persistAndFlush($tag3);

        // 使用查询构建器进行模糊搜索 - 只查询我们创建的标签
        $queryBuilder = $this->repository->createQueryBuilder('t')
            ->where('t.name LIKE :pattern')
            ->andWhere('t.id IN (:tagIds)')
            ->setParameter('pattern', '%推荐%')
            ->setParameter('tagIds', [$tag1->getId(), $tag2->getId(), $tag3->getId()])
        ;

        $tags = $queryBuilder->getQuery()->getResult();

        $this->assertCount(2, $tags);
        $tagNames = array_map(fn (Tag $tag) => $tag->getName(), $tags);
        $this->assertContains('热门推荐', $tagNames);
        $this->assertContains('推荐文章', $tagNames);
    }

    public function testFindTagsWithCount(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $group = new TagGroup();
        $group->setName('Test Group');
        $group = $this->persistAndFlush($group);
        self::assertInstanceOf(TagGroup::class, $group);

        $tag1 = new Tag();
        $tag1->setName('Tag 1');
        $tag1->setValid(true);
        $tag1->setGroups($group);
        $this->persistAndFlush($tag1);

        $tag2 = new Tag();
        $tag2->setName('Tag 2');
        $tag2->setValid(false);
        $tag2->setGroups($group);
        $this->persistAndFlush($tag2);

        // 只统计我们创建的标签
        $validTagCount = $this->repository->count(['valid' => true, 'id' => [$tag1->getId(), $tag2->getId()]]);
        $invalidTagCount = $this->repository->count(['valid' => false, 'id' => [$tag1->getId(), $tag2->getId()]]);
        $totalTagCount = $this->repository->count(['id' => [$tag1->getId(), $tag2->getId()]]);

        $this->assertEquals(1, $validTagCount);
        $this->assertEquals(1, $invalidTagCount);
        $this->assertEquals(2, $totalTagCount);
    }

    public function testFindOrderedTags(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $tag1 = new Tag();
        $tag1->setName('Z Tag');
        $tag1->setValid(true);
        $this->persistAndFlush($tag1);

        $tag2 = new Tag();
        $tag2->setName('A Tag');
        $tag2->setValid(true);
        $this->persistAndFlush($tag2);

        $tag3 = new Tag();
        $tag3->setName('M Tag');
        $tag3->setValid(true);
        $this->persistAndFlush($tag3);

        // 只查询我们创建的标签
        $orderedTags = $this->repository->findBy(['id' => [$tag1->getId(), $tag2->getId(), $tag3->getId()]], ['name' => 'ASC']);

        $this->assertCount(3, $orderedTags);
        $this->assertEquals('A Tag', $orderedTags[0]->getName());
        $this->assertEquals('M Tag', $orderedTags[1]->getName());
        $this->assertEquals('Z Tag', $orderedTags[2]->getName());
    }

    public function testFindWithLimitAndOffset(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        // 创建5个标签
        $tagIds = [];
        for ($i = 1; $i <= 5; ++$i) {
            $tag = new Tag();
            $tag->setName("Tag {$i}");
            $tag->setValid(true);
            $tag = $this->persistAndFlush($tag);
            $tagIds[] = $tag->getId();
        }

        // 使用查询构建器来确保只查询我们创建的标签
        $queryBuilder = $this->repository->createQueryBuilder('t')
            ->where('t.id IN (:tagIds)')
            ->setParameter('tagIds', $tagIds)
            ->orderBy('t.id', 'ASC')
        ;

        // 获取前2个
        $firstPage = $queryBuilder->setFirstResult(0)->setMaxResults(2)->getQuery()->getResult();
        $this->assertCount(2, $firstPage);

        // 获取第3-4个
        $secondPage = $queryBuilder->setFirstResult(2)->setMaxResults(2)->getQuery()->getResult();
        $this->assertCount(2, $secondPage);

        // 获取最后一个
        $lastPage = $queryBuilder->setFirstResult(4)->setMaxResults(2)->getQuery()->getResult();
        $this->assertCount(1, $lastPage);
    }

    public function testComplexQuery(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $group1 = new TagGroup();
        $group1->setName('Group 1');
        $group1 = $this->persistAndFlush($group1);
        self::assertInstanceOf(TagGroup::class, $group1);

        $group2 = new TagGroup();
        $group2->setName('Group 2');
        $group2 = $this->persistAndFlush($group2);
        self::assertInstanceOf(TagGroup::class, $group2);

        $tag1 = new Tag();
        $tag1->setName('热门标签');
        $tag1->setValid(true);
        $tag1->setGroups($group1);
        $this->persistAndFlush($tag1);

        $tag2 = new Tag();
        $tag2->setName('推荐标签');
        $tag2->setValid(false);
        $tag2->setGroups($group1);
        $this->persistAndFlush($tag2);

        $tag3 = new Tag();
        $tag3->setName('最新标签');
        $tag3->setValid(true);
        $tag3->setGroups($group2);
        $this->persistAndFlush($tag3);

        // 复杂查询：查找特定分组下的有效标签
        $queryBuilder = $this->repository->createQueryBuilder('t')
            ->join('t.groups', 'g')
            ->where('t.valid = :valid')
            ->andWhere('g.name = :groupName')
            ->setParameter('valid', true)
            ->setParameter('groupName', 'Group 1')
        ;

        $tags = $queryBuilder->getQuery()->getResult();

        $this->assertCount(1, $tags);
        $this->assertEquals('热门标签', $tags[0]->getName());
    }

    public function testBulkUpdate(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $tag1 = new Tag();
        $tag1->setName('Tag 1');
        $tag1->setValid(false);
        $this->persistAndFlush($tag1);

        $tag2 = new Tag();
        $tag2->setName('Tag 2');
        $tag2->setValid(false);
        $this->persistAndFlush($tag2);

        // 批量更新 - 只更新我们创建的标签
        $queryBuilder = $this->repository->createQueryBuilder('t')
            ->update()
            ->set('t.valid', ':valid')
            ->where('t.id IN (:tagIds)')
            ->setParameter('valid', true)
            ->setParameter('tagIds', [$tag1->getId(), $tag2->getId()])
        ;

        $updatedCount = $queryBuilder->getQuery()->execute();

        $this->assertEquals(2, $updatedCount);

        // 验证更新结果 - 只查询我们创建的标签
        self::getEntityManager()->clear();
        $validTags = $this->repository->findBy(['valid' => true, 'id' => [$tag1->getId(), $tag2->getId()]]);
        $this->assertCount(2, $validTags);
    }

    protected function onSetUp(): void
    {
        $this->repository = self::getService(TagRepository::class);
    }

    protected function createNewEntity(): object
    {
        $entity = new Tag();
        $entity->setName('Test Tag ' . uniqid());
        $entity->setValid(true);

        return $entity;
    }

    protected function getRepository(): TagRepository
    {
        return $this->repository;
    }
}
