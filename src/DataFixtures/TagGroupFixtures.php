<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\TagManageBundle\Entity\TagGroup;

#[When(env: 'dev')]
class TagGroupFixtures extends Fixture
{
    public const TAG_GROUP_SPORTS_REFERENCE = 'tag-group-sports';

    public function load(ObjectManager $manager): void
    {
        $tagGroup = new TagGroup();
        $tagGroup->setName('体育运动');

        $manager->persist($tagGroup);
        $this->addReference(self::TAG_GROUP_SPORTS_REFERENCE, $tagGroup);

        $manager->flush();
    }
}
