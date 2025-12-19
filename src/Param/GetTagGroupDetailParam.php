<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Param;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class GetTagGroupDetailParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '标签组ID')]
        #[Assert\NotBlank]
        public string $groupId,

        #[MethodParam(description: '是否包含关联的标签列表')]
        public bool $includeTags = false,
    ) {
    }
}
