<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Param;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPCPaginatorBundle\Param\PaginatorParamInterface;

readonly class GetTagListParam implements PaginatorParamInterface
{
    public function __construct(
        #[MethodParam(description: '标签组ID')]
        public ?string $groupId = null,

        #[MethodParam(description: '搜索关键词')]
        public ?string $keyword = null,

        #[MethodParam(description: '是否只获取有效的标签')]
        public bool $validOnly = true,

        #[MethodParam(description: '排序字段')]
        #[Assert\Choice(choices: ['name', 'createTime', 'updateTime', 'usage'])]
        public string $orderBy = 'createTime',

        #[MethodParam(description: '排序方向')]
        #[Assert\Choice(choices: ['ASC', 'DESC'])]
        public string $orderDir = 'DESC',

        #[MethodParam(description: '是否包含使用统计')]
        public bool $includeUsageStats = false,

        #[MethodParam(description: '每页条数')]
        #[Assert\Range(min: 1, max: 2000)]
        public int $pageSize = 10,

        #[MethodParam(description: '当前页数')]
        #[Assert\Range(min: 1, max: 1000)]
        public int $currentPage = 1,

        #[MethodParam(description: '上一次拉取时，最后一条数据的主键ID')]
        public ?int $lastId = null,
    ) {
    }
}
