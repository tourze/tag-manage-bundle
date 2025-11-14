# TagManageBundle

[English](README.md) | [中文](README.zh-CN.md)

一个通用的标签管理系统 Symfony Bundle，提供完整的标签和标签分组管理功能，支持 EasyAdmin 后台管理和 JsonRPC API 接口。

## 功能特性

- ✅ **标签管理**：创建、编辑、删除标签，支持唯一名称验证
- ✅ **标签分组**：支持将标签按组分类管理
- ✅ **EasyAdmin 集成**：提供完整的后台管理界面
- ✅ **JsonRPC API**：提供丰富的 API 接口支持
- ✅ **缓存支持**：API 结果自动缓存，提升性能
- ✅ **分页查询**：支持标签列表分页和过滤
- ✅ **审计字段**：自动记录创建时间、更新时间、操作人等信息
- ✅ **软删除**：支持标签的有效状态管理
- ✅ **搜索功能**：支持按关键词搜索标签

## 系统要求

- PHP 8.1+
- Symfony 7.3+
- Doctrine ORM 3.0+

## 安装

使用 Composer 安装：

```bash
composer require tourze/tag-manage-bundle
```

## 配置

### 1. 注册 Bundle

在 `config/bundles.php` 中注册：

```php
return [
    // ...
    Tourze\TagManageBundle\TagManageBundle::class => ['all' => true],
];
```

### 2. 数据库迁移

Bundle 会自动创建以下数据表：

- `cms_tag`：标签表
- `cms_tag_group`：标签分组表

运行数据库迁移：

```bash
php bin/console doctrine:migrations:migrate
```

### 3. 加载 Fixtures（可选）

如果要加载测试数据：

```bash
php bin/console doctrine:fixtures:load --group=tag
```

## 使用方法

### 1. 实体使用

```php
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Entity\TagGroup;

// 创建标签分组
$group = new TagGroup();
$group->setName('技术分类');

// 创建标签
$tag = new Tag();
$tag->setName('PHP');
$tag->setGroups($group);
$tag->setValid(true);
```

### 2. EasyAdmin 后台管理

Bundle 自动注册后台管理路由：

- 标签管理：`/admin/cms/tag`
- 标签分组管理：`/admin/cms/tag-group`

### 3. JsonRPC API 使用

#### 获取标签列表

```json
{
  "jsonrpc": "2.0",
  "method": "GetTagList",
  "params": {
    "groupId": "group_id",
    "keyword": "搜索关键词",
    "validOnly": true,
    "orderBy": "createTime",
    "orderDir": "DESC",
    "includeUsageStats": true
  },
  "id": 1
}
```

#### 获取标签分组列表

```json
{
  "jsonrpc": "2.0",
  "method": "GetTagGroupList",
  "params": {
    "orderBy": "name",
    "orderDir": "ASC"
  },
  "id": 2
}
```

#### 获取标签分组详情

```json
{
  "jsonrpc": "2.0",
  "method": "GetTagGroupDetail",
  "params": {
    "id": "group_id",
    "includeTags": true
  },
  "id": 3
}
```

#### 搜索标签

```json
{
  "jsonrpc": "2.0",
  "method": "SearchTags",
  "params": {
    "keyword": "PHP",
    "groupId": "group_id",
    "validOnly": true
  },
  "id": 4
}
```

### 4. 仓储查询

```php
use Tourze\TagManageBundle\Repository\TagRepository;
use Tourze\TagManageBundle\Repository\TagGroupRepository;

// 标签查询
$tags = $tagRepository->findBy(['valid' => true], ['name' => 'ASC']);
$tag = $tagRepository->findOneBy(['name' => 'PHP']);

// 标签分组查询
$groups = $tagGroupRepository->findAll();
$group = $tagGroupRepository->findOneBy(['name' => '技术分类']);
```

## 配置选项

在 `config/packages/tag_manage.yaml` 中可以配置：

```yaml
tag_manage:
    # 缓存时间（秒），默认 600
    cache_duration: 600

    # 默认分页大小，默认 20
    default_page_size: 20

    # 是否启用使用统计，默认 false
    enable_usage_stats: false
```

## 实体字段说明

### Tag（标签）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | integer | 主键 ID |
| name | string(60) | 标签名称（唯一） |
| groups | TagGroup | 所属标签分组 |
| valid | boolean | 是否有效状态 |
| createTime | datetime | 创建时间 |
| updateTime | datetime | 更新时间 |
| createUser | string | 创建用户 |
| updateUser | string | 更新用户 |

### TagGroup（标签分组）

| 字段 | 类型 | 说明 |
|------|------|------|
| id | string | 雪花 ID 主键 |
| name | string(60) | 分组名称 |
| createTime | datetime | 创建时间 |
| updateTime | datetime | 更新时间 |
| createUser | string | 创建用户 |
| updateUser | string | 更新用户 |

## API 响应格式

### 列表接口响应

```json
{
  "jsonrpc": "2.0",
  "result": {
    "list": [
      {
        "id": 1,
        "name": "PHP",
        "valid": true,
        "group": {
          "id": "1234567890123456789",
          "name": "技术分类"
        },
        "createTime": "2024-01-01 12:00:00",
        "updateTime": "2024-01-01 12:00:00",
        "usageCount": 156,
        "lastUsedTime": "2024-01-15 10:30:00"
      }
    ],
    "pagination": {
      "current": 1,
      "pageSize": 20,
      "total": 100,
      "hasMore": true
    }
  },
  "id": 1
}
```

## 测试

运行测试套件：

```bash
# 运行所有测试
php bin/console phpunit tests/TagManageBundle/

# 运行特定测试
php bin/console phpunit tests/TagManageBundle/Entity/TagTest.php

# 运行覆盖率测试
php bin/console phpunit --coverage-html coverage tests/TagManageBundle/
```

## 依赖包说明

Bundle 依赖以下关键包：

- **EasyAdminBundle**：提供后台管理界面
- **Doctrine ORM**：数据持久化
- **JsonRPC Core**：API 接口支持
- **JsonRPC Cache**：API 缓存功能
- **JsonRPC Paginator**：分页功能
- **Doctrine Timestamp Bundle**：时间戳字段
- **Doctrine User Bundle**：用户审计字段
- **Doctrine IP Bundle**：IP 审计字段

## 更新日志

### 1.0.0

- 初始版本发布
- 支持标签和标签分组管理
- 集成 EasyAdmin 后台管理
- 提供 JsonRPC API 接口
- 支持缓存和分页

## 贡献指南

欢迎提交 Issue 和 Pull Request！

## 许可证

MIT License