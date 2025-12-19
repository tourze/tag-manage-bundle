<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Param;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class SearchTagsParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '搜索关键词')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 50)]
        public string $keyword,

        #[MethodParam(description: '最大返回数量')]
        #[Assert\Range(min: 1, max: 50)]
        public int $limit = 10,

        #[MethodParam(description: '是否只搜索有效标签')]
        public bool $validOnly = true,

        #[MethodParam(description: '标签组ID过滤')]
        public ?string $groupId = null,

        #[MethodParam(description: '是否按使用频率排序')]
        public bool $orderByUsage = false,
    ) {
    }
}
