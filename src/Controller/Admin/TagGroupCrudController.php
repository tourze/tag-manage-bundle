<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\TagManageBundle\Entity\TagGroup;

/**
 * 标签分组管理控制器
 */
#[AdminCrud(routePath: '/cms/tag-group', routeName: 'cms_tag_group')]
final class TagGroupCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TagGroup::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('标签分组')
            ->setEntityLabelInPlural('标签分组管理')
            ->setPageTitle('index', '标签分组列表')
            ->setPageTitle('new', '新建标签分组')
            ->setPageTitle('edit', '编辑标签分组')
            ->setPageTitle('detail', '标签分组详情')
            ->setHelp('index', '管理标签分组，提升标签体系的组织性')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['name'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
            ->setMaxLength(9999)
        ;

        yield TextField::new('name', '分组名称')
            ->setHelp('标签分组的名称')
            ->setRequired(true)
        ;

        // 关联标签字段
        yield AssociationField::new('tags', '包含标签')
            ->setHelp('此分组包含的标签')
            ->setCrudController(TagCrudController::class)
            ->onlyOnDetail()
            ->formatValue(function ($value) {
                if ($value instanceof \Countable) {
                    return sprintf('共 %d 个标签', count($value));
                }

                return '0 个标签';
            })
        ;

        // 审计字段
        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', '分组名称'))
        ;
    }
}
