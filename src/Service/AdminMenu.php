<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Entity\TagGroup;

/**
 * 标签管理模块菜单服务
 */
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('内容管理')) {
            $item->addChild('内容管理');
        }

        $contentMenu = $item->getChild('内容管理');
        if (null === $contentMenu) {
            return;
        }

        // 标签分组管理
        $contentMenu->addChild('标签分组')
            ->setUri($this->linkGenerator->getCurdListPage(TagGroup::class))
            ->setAttribute('icon', 'fas fa-folder')
        ;

        // 标签管理
        $contentMenu->addChild('标签管理')
            ->setUri($this->linkGenerator->getCurdListPage(Tag::class))
            ->setAttribute('icon', 'fas fa-tags')
        ;
    }
}
