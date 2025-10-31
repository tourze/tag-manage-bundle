<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Traits\IpTraceableAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\TagManageBundle\Repository\TagRepository;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'cms_tag', options: ['comment' => '内容标签表'])]
class Tag implements \Stringable
{
    use BlameableAware;
    use IpTraceableAware;
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private int $id = 0;

    #[Groups(groups: ['restful_read'])]
    #[ORM\Column(type: Types::STRING, length: 60, unique: true, options: ['comment' => '标签名'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 60)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'tags')]
    private ?TagGroup $groups = null;

    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    #[Assert\Type(type: 'bool')]
    private ?bool $valid = false;

    public function __construct()
    {
    }

    public function __toString(): string
    {
        if (0 === $this->getId()) {
            return '';
        }

        return $this->getName() ?? '';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): void
    {
        $this->valid = $valid;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getGroups(): ?TagGroup
    {
        return $this->groups;
    }

    public function setGroups(?TagGroup $groups): void
    {
        $this->groups = $groups;
    }
}
