<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Controller\Admin;

use CmsBundle\Controller\Admin\EntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\TagManageBundle\Entity\Tag;

/**
 * 标签管理控制器
 */
#[AdminCrud(routePath: '/cms/tag', routeName: 'cms_tag')]
final class TagCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Tag::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('标签')
            ->setEntityLabelInPlural('标签管理')
            ->setPageTitle('index', '标签列表')
            ->setPageTitle('new', '新建标签')
            ->setPageTitle('edit', '编辑标签')
            ->setPageTitle('detail', '标签详情')
            ->setHelp('index', '管理内容标签，为内容提供分类和标记功能')
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

        yield TextField::new('name', '标签名称')
            ->setHelp('标签的名称，必须唯一')
            ->setRequired(true)
        ;

        yield AssociationField::new('groups', '所属分组')
            ->setHelp('标签所属的分组')
            ->setCrudController(TagGroupCrudController::class)
            ->autocomplete()
        ;

        yield BooleanField::new('valid', '状态')
            ->setHelp('是否启用该标签')
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
            ->add(TextFilter::new('name', '标签名称'))
            ->add(EntityFilter::new('groups', '所属分组'))
            ->add(BooleanFilter::new('valid', '状态'))
        ;
    }
}
