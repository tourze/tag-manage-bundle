<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Entity\TagGroup;

#[When(env: 'dev')]
class TagFixtures extends Fixture implements DependentFixtureInterface
{
    public const TAG_REFERENCE_PREFIX = 'tag-';

    public function load(ObjectManager $manager): void
    {
        // 获取 TagGroup 的引用
        /** @var TagGroup $tagGroup */
        $tagGroup = $this->getReference(TagGroupFixtures::TAG_GROUP_SPORTS_REFERENCE, TagGroup::class);

        $tags = ['热门', '推荐', '最新', '精选'];

        foreach ($tags as $index => $tagName) {
            $tag = new Tag();
            $tag->setName($tagName);
            $tag->setValid(true);
            $tag->setGroups($tagGroup);

            $manager->persist($tag);
            $this->addReference(self::TAG_REFERENCE_PREFIX . $index, $tag);
        }

        $manager->flush();
    }

    /**
     * @return array<class-string<FixtureInterface>>
     */
    public function getDependencies(): array
    {
        return [
            TagGroupFixtures::class,
        ];
    }
}
