<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Entity\TagGroup;
use Tourze\TagManageBundle\Repository\TagGroupRepository;

/**
 * @internal
 */
#[CoversClass(TagGroupRepository::class)]
#[RunTestsInSeparateProcesses]
final class TagGroupRepositoryAdvancedTest extends AbstractRepositoryTestCase
{
    private TagGroupRepository $repository;

    public function testFindTagGroupsWithTags(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $group1 = new TagGroup();
        $group1->setName('Group with Tags');
        $group1 = $this->persistAndFlush($group1);
        /** @var TagGroup $group1 */
        $group2 = new TagGroup();
        $group2->setName('Empty Group');
        $group2 = $this->persistAndFlush($group2);
        /** @var TagGroup $group2 */
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

        // 查询包含标签的分组，只查找我们测试中创建的分组
        $queryBuilder = $this->repository->createQueryBuilder('tg')
            ->leftJoin('tg.tags', 't')
            ->where('t.id IS NOT NULL')
            ->andWhere('tg.name = :groupName')
            ->setParameter('groupName', 'Group with Tags')
            ->groupBy('tg.id')
        ;

        $groupsWithTags = $queryBuilder->getQuery()->getResult();

        $this->assertCount(1, $groupsWithTags);
        $this->assertEquals('Group with Tags', $groupsWithTags[0]->getName());
    }

    public function testFindTagGroupsWithTagCount(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $group1 = new TagGroup();
        $group1->setName('Group 1');
        $group1 = $this->persistAndFlush($group1);
        /** @var TagGroup $group1 */
        $group2 = new TagGroup();
        $group2->setName('Group 2');
        $group2 = $this->persistAndFlush($group2);
        /** @var TagGroup $group2 */

        // Group 1 有2个标签
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

        // Group 2 有1个标签
        $tag3 = new Tag();
        $tag3->setName('Tag 3');
        $tag3->setValid(true);
        $tag3->setGroups($group2);
        $this->persistAndFlush($tag3);

        // 使用DQL获取分组及其标签数量
        $queryBuilder = $this->repository->createQueryBuilder('tg')
            ->leftJoin('tg.tags', 't')
            ->select('tg, COUNT(t.id) as tagCount')
            ->where('tg.id IN (:groupIds)')
            ->setParameter('groupIds', [$group1->getId(), $group2->getId()])
            ->groupBy('tg.id')
            ->orderBy('tagCount', 'DESC')
        ;

        $result = $queryBuilder->getQuery()->getResult();

        $this->assertCount(2, $result);
        $this->assertEquals('Group 1', $result[0][0]->getName());
        $this->assertEquals(2, $result[0]['tagCount']);
        $this->assertEquals('Group 2', $result[1][0]->getName());
        $this->assertEquals(1, $result[1]['tagCount']);
    }

    public function testFindTagGroupsByName(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $group1 = new TagGroup();
        $group1->setName('体育运动');
        $this->persistAndFlush($group1);

        $group2 = new TagGroup();
        $group2->setName('体育新闻');
        $this->persistAndFlush($group2);

        $group3 = new TagGroup();
        $group3->setName('科技资讯');
        $this->persistAndFlush($group3);

        // 模糊搜索包含"体育"的分组
        $queryBuilder = $this->repository->createQueryBuilder('tg')
            ->where('tg.name LIKE :pattern')
            ->andWhere('tg.id IN (:groupIds)')
            ->setParameter('pattern', '%体育%')
            ->setParameter('groupIds', [$group1->getId(), $group2->getId(), $group3->getId()])
        ;

        $sportsGroups = $queryBuilder->getQuery()->getResult();

        $this->assertCount(2, $sportsGroups);
        $groupNames = array_map(fn (TagGroup $group) => $group->getName(), $sportsGroups);
        $this->assertContains('体育运动', $groupNames);
        $this->assertContains('体育新闻', $groupNames);
    }

    public function testFindTagGroupsWithValidTagsOnly(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $group = new TagGroup();
        $group->setName('Mixed Group');
        $group = $this->persistAndFlush($group);
        /** @var TagGroup $group */
        self::assertInstanceOf(TagGroup::class, $group);

        $validTag = new Tag();
        $validTag->setName('Valid Tag');
        $validTag->setValid(true);
        $validTag->setGroups($group);
        $this->persistAndFlush($validTag);

        $invalidTag = new Tag();
        $invalidTag->setName('Invalid Tag');
        $invalidTag->setValid(false);
        $invalidTag->setGroups($group);
        $this->persistAndFlush($invalidTag);

        // 查询只包含有效标签的分组统计
        $queryBuilder = $this->repository->createQueryBuilder('tg')
            ->leftJoin('tg.tags', 't', 'WITH', 't.valid = true')
            ->select('tg, COUNT(t.id) as validTagCount')
            ->where('tg.id = :groupId')
            ->setParameter('groupId', $group->getId())
            ->groupBy('tg.id')
        ;

        $result = $queryBuilder->getQuery()->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals('Mixed Group', $result[0][0]->getName());
        $this->assertEquals(1, $result[0]['validTagCount']);
    }

    public function testDeleteTagGroupWithoutTags(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $emptyGroup = new TagGroup();
        $emptyGroup->setName('Empty Group');
        $emptyGroup = $this->persistAndFlush($emptyGroup);
        /** @var TagGroup $emptyGroup */
        self::assertInstanceOf(TagGroup::class, $emptyGroup);

        $groupWithTags = new TagGroup();
        $groupWithTags->setName('Group with Tags');
        $groupWithTags = $this->persistAndFlush($groupWithTags);
        /** @var TagGroup $groupWithTags */
        self::assertInstanceOf(TagGroup::class, $groupWithTags);

        $tag = new Tag();
        $tag->setName('Test Tag');
        $tag->setValid(true);
        $tag->setGroups($groupWithTags);
        $this->persistAndFlush($tag);

        // 删除空分组
        $this->repository->remove($emptyGroup);
        self::getEntityManager()->clear();

        // 验证删除结果 - 只查询我们创建的分组
        $remainingGroups = $this->repository->findBy(['id' => [$emptyGroup->getId(), $groupWithTags->getId()]]);
        $this->assertCount(1, $remainingGroups);
        $this->assertEquals('Group with Tags', $remainingGroups[0]->getName());
    }

    public function testFindGroupsOrderedByCreationTime(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $group1 = new TagGroup();
        $group1->setName('First Group');
        $this->persistAndFlush($group1);

        // 模拟时间间隔
        sleep(1);

        $group2 = new TagGroup();
        $group2->setName('Second Group');
        $this->persistAndFlush($group2);

        sleep(1);

        $group3 = new TagGroup();
        $group3->setName('Third Group');
        $this->persistAndFlush($group3);

        // 按创建时间降序查询 - 只查询我们创建的分组
        $groupsDesc = $this->repository->findBy(
            ['id' => [$group1->getId(), $group2->getId(), $group3->getId()]],
            ['createTime' => 'DESC']
        );

        $this->assertCount(3, $groupsDesc);
        $this->assertEquals('Third Group', $groupsDesc[0]->getName());
        $this->assertEquals('Second Group', $groupsDesc[1]->getName());
        $this->assertEquals('First Group', $groupsDesc[2]->getName());

        // 按创建时间升序查询 - 只查询我们创建的分组
        $groupsAsc = $this->repository->findBy(
            ['id' => [$group1->getId(), $group2->getId(), $group3->getId()]],
            ['createTime' => 'ASC']
        );

        $this->assertCount(3, $groupsAsc);
        $this->assertEquals('First Group', $groupsAsc[0]->getName());
        $this->assertEquals('Second Group', $groupsAsc[1]->getName());
        $this->assertEquals('Third Group', $groupsAsc[2]->getName());
    }

    public function testComplexJoinQuery(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $group = new TagGroup();
        $group->setName('Test Group');
        $group = $this->persistAndFlush($group);
        /** @var TagGroup $group */
        self::assertInstanceOf(TagGroup::class, $group);

        $validTag = new Tag();
        $validTag->setName('Valid Tag with UniqueTest');
        $validTag->setValid(true);
        $validTag->setGroups($group);
        $this->persistAndFlush($validTag);

        $invalidTag = new Tag();
        $invalidTag->setName('Invalid Tag with UniqueTest');
        $invalidTag->setValid(false);
        $invalidTag->setGroups($group);
        $this->persistAndFlush($invalidTag);

        // 复杂查询：查找包含特定关键字的有效标签的分组
        $queryBuilder = $this->repository->createQueryBuilder('tg')
            ->innerJoin('tg.tags', 't')
            ->where('t.valid = :valid')
            ->andWhere('t.name LIKE :keyword')
            ->setParameter('valid', true)
            ->setParameter('keyword', '%UniqueTest%')
        ;

        $groups = $queryBuilder->getQuery()->getResult();

        $this->assertCount(1, $groups);
        $this->assertEquals('Test Group', $groups[0]->getName());
    }

    protected function onSetUp(): void
    {
        $this->repository = self::getService(TagGroupRepository::class);
    }

    protected function createNewEntity(): object
    {
        $entity = new TagGroup();
        $entity->setName('Test Group ' . uniqid());

        return $entity;
    }

    protected function getRepository(): TagGroupRepository
    {
        return $this->repository;
    }
}
