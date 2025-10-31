<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Entity\TagGroup;
use Tourze\TagManageBundle\Service\AdminMenu;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // 实现抽象方法，可以为空实现
    }

    public function testAdminMenuCreatesContentManagementMenu(): void
    {
        // Mock LinkGeneratorInterface 并注入到容器
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $linkGenerator
            ->method('getCurdListPage')
            ->willReturnMap([
                [TagGroup::class, '/admin/tag/tag-group'],
                [Tag::class, '/admin/tag/tag'],
            ])
        ;

        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);
        $adminMenu = self::getService(AdminMenu::class);

        // 创建根菜单项
        $menuFactory = new MenuFactory();
        $rootMenu = $menuFactory->createItem('root');

        // 调用菜单服务
        $adminMenu($rootMenu);

        // 验证内容管理菜单已创建
        $contentMenu = $rootMenu->getChild('内容管理');
        $this->assertNotNull($contentMenu);

        // 验证各个子菜单项已创建
        $this->assertNotNull($contentMenu->getChild('标签分组'));
        $this->assertNotNull($contentMenu->getChild('标签管理'));

        // 验证菜单链接正确
        $tagGroupMenuItem = $contentMenu->getChild('标签分组');
        $tagMenuItem = $contentMenu->getChild('标签管理');

        $this->assertNotNull($tagGroupMenuItem);
        $this->assertNotNull($tagMenuItem);
        $this->assertSame('/admin/tag/tag-group', $tagGroupMenuItem->getUri());
        $this->assertSame('/admin/tag/tag', $tagMenuItem->getUri());

        // 验证图标属性
        $this->assertSame('fas fa-folder', $tagGroupMenuItem->getAttribute('icon'));
        $this->assertSame('fas fa-tags', $tagMenuItem->getAttribute('icon'));
    }

    public function testAdminMenuWorksWithExistingContentMenu(): void
    {
        // Mock LinkGeneratorInterface 并注入到容器
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $linkGenerator
            ->method('getCurdListPage')
            ->willReturnMap([
                [TagGroup::class, '/admin/tag/tag-group'],
                [Tag::class, '/admin/tag/tag'],
            ])
        ;

        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);
        $adminMenu = self::getService(AdminMenu::class);

        // 创建已有内容管理菜单的根菜单
        $menuFactory = new MenuFactory();
        $rootMenu = $menuFactory->createItem('root');
        $rootMenu->addChild('内容管理'); // 预先创建内容管理菜单

        // 调用菜单服务
        $adminMenu($rootMenu);

        // 验证不会重复创建内容管理菜单
        $contentMenu = $rootMenu->getChild('内容管理');
        $this->assertNotNull($contentMenu);

        // 验证子菜单已添加到现有菜单中
        $this->assertNotNull($contentMenu->getChild('标签分组'));
        $this->assertNotNull($contentMenu->getChild('标签管理'));
    }

    public function testAdminMenuIsCallable(): void
    {
        $adminMenu = self::getService(AdminMenu::class);
        $this->assertIsCallable($adminMenu);
    }

    public function testAdminMenuHandlesNullContentMenu(): void
    {
        // Mock LinkGeneratorInterface
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);

        $adminMenu = self::getService(AdminMenu::class);

        // 创建一个特殊的菜单工厂，返回 null 的 getChild
        $rootMenu = $this->createMock(ItemInterface::class);
        $rootMenu->method('getChild')->willReturn(null);
        $rootMenu->expects($this->once())->method('addChild')->with('内容管理');

        // 调用菜单服务 - 应该不会抛出异常
        $adminMenu($rootMenu);

        // 如果执行到这里，说明没有抛出异常
        $this->assertTrue(true);
    }
}
