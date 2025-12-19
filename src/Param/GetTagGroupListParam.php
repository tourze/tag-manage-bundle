<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Param;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class GetTagGroupListParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '页码')]
        #[Assert\PositiveOrZero]
        public int $page = 1,

        #[MethodParam(description: '每页数量')]
        #[Assert\Range(min: 1, max: 100)]
        public int $limit = 20,

        #[MethodParam(description: '搜索关键字')]
        #[Assert\Length(max: 100)]
        public ?string $keyword = null,

        #[MethodParam(description: '是否包含标签统计')]
        public bool $withTagCount = false,
    ) {
    }
}
