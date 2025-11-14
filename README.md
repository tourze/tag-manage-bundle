# TagManageBundle

[English](README.md) | [中文](README.zh-CN.md)

A comprehensive tag management system for Symfony, providing complete tag and tag group management features with EasyAdmin backend integration and JsonRPC API support.

## Features

-  **Tag Management**: Create, edit, delete tags with unique name validation
-  **Tag Groups**: Organize tags into groups for better management
-  **EasyAdmin Integration**: Complete backend management interface
-  **JsonRPC API**: Rich API interface support
-  **Cache Support**: Automatic API result caching for performance
-  **Pagination**: Tag list pagination and filtering
-  **Audit Fields**: Automatic tracking of creation/update time and users
-  **Soft Delete**: Tag validity status management
-  **Search**: Keyword-based tag search functionality

## Requirements

- PHP 8.1+
- Symfony 7.3+
- Doctrine ORM 3.0+

## Installation

Install using Composer:

```bash
composer require tourze/tag-manage-bundle
```

## Configuration

### 1. Register Bundle

Register in `config/bundles.php`:

```php
return [
    // ...
    Tourze\TagManageBundle\TagManageBundle::class => ['all' => true],
];
```

### 2. Database Migration

Bundle automatically creates the following database tables:

- `cms_tag`: Tags table
- `cms_tag_group`: Tag groups table

Run database migration:

```bash
php bin/console doctrine:migrations:migrate
```

### 3. Load Fixtures (Optional)

To load test data:

```bash
php bin/console doctrine:fixtures:load --group=tag
```

## Usage

### 1. Entity Usage

```php
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Entity\TagGroup;

// Create tag group
$group = new TagGroup();
$group->setName('Technology');

// Create tag
$tag = new Tag();
$tag->setName('PHP');
$tag->setGroups($group);
$tag->setValid(true);
```

### 2. EasyAdmin Backend Management

Bundle automatically registers backend management routes:

- Tag Management: `/admin/cms/tag`
- Tag Group Management: `/admin/cms/tag-group`

### 3. JsonRPC API Usage

#### Get Tag List

```json
{
  "jsonrpc": "2.0",
  "method": "GetTagList",
  "params": {
    "groupId": "group_id",
    "keyword": "search keyword",
    "validOnly": true,
    "orderBy": "createTime",
    "orderDir": "DESC",
    "includeUsageStats": true
  },
  "id": 1
}
```

#### Get Tag Group List

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

#### Get Tag Group Detail

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

#### Search Tags

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

### 4. Repository Queries

```php
use Tourze\TagManageBundle\Repository\TagRepository;
use Tourze\TagManageBundle\Repository\TagGroupRepository;

// Tag queries
$tags = $tagRepository->findBy(['valid' => true], ['name' => 'ASC']);
$tag = $tagRepository->findOneBy(['name' => 'PHP']);

// Tag group queries
$groups = $tagGroupRepository->findAll();
$group = $tagGroupRepository->findOneBy(['name' => 'Technology']);
```

## Configuration Options

Configure in `config/packages/tag_manage.yaml`:

```yaml
tag_manage:
    # Cache duration in seconds, default 600
    cache_duration: 600

    # Default page size, default 20
    default_page_size: 20

    # Enable usage statistics, default false
    enable_usage_stats: false
```

## Entity Fields

### Tag

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Primary key ID |
| name | string(60) | Tag name (unique) |
| groups | TagGroup | Belonging tag group |
| valid | boolean | Valid status |
| createTime | datetime | Creation time |
| updateTime | datetime | Update time |
| createUser | string | Creation user |
| updateUser | string | Update user |

### TagGroup

| Field | Type | Description |
|-------|------|-------------|
| id | string | Snowflake ID primary key |
| name | string(60) | Group name |
| createTime | datetime | Creation time |
| updateTime | datetime | Update time |
| createUser | string | Creation user |
| updateUser | string | Update user |

## API Response Format

### List Interface Response

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
          "name": "Technology"
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

## Testing

Run the test suite:

```bash
# Run all tests
php bin/console phpunit tests/TagManageBundle/

# Run specific tests
php bin/console phpunit tests/TagManageBundle/Entity/TagTest.php

# Run coverage test
php bin/console phpunit --coverage-html coverage tests/TagManageBundle/
```

## Dependencies

Bundle depends on the following key packages:

- **EasyAdminBundle**: Backend management interface
- **Doctrine ORM**: Data persistence
- **JsonRPC Core**: API interface support
- **JsonRPC Cache**: API caching functionality
- **JsonRPC Paginator**: Pagination functionality
- **Doctrine Timestamp Bundle**: Timestamp fields
- **Doctrine User Bundle**: User audit fields
- **Doctrine IP Bundle**: IP audit fields

## Changelog

### 1.0.0

- Initial release
- Tag and tag group management support
- EasyAdmin backend integration
- JsonRPC API interface
- Cache and pagination support

## Contributing

Issues and Pull Requests are welcome!

## License

MIT License