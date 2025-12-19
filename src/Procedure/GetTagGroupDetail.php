<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Procedure;

use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPCCacheBundle\Procedure\CacheableProcedure;
use Tourze\TagManageBundle\Exception\TagManageException;
use Tourze\TagManageBundle\Param\GetTagGroupDetailParam;
use Tourze\TagManageBundle\Repository\TagGroupRepository;

#[MethodTag(name: '标签管理')]
#[MethodDoc(summary: '获取标签组详细信息')]
#[MethodExpose(method: 'GetTagGroupDetail')]
final class GetTagGroupDetail extends CacheableProcedure
{
    public function __construct(
        private readonly TagGroupRepository $tagGroupRepository,
    ) {
    }

    /**
     * @phpstan-param GetTagGroupDetailParam $param
     */
    public function execute(GetTagGroupDetailParam|RpcParamInterface $param): ArrayResult
    {
        $tagGroup = $this->tagGroupRepository->find($param->groupId);
        if (null === $tagGroup) {
            throw new ApiException('标签组不存在');
        }

        $result = [
            'id' => $tagGroup->getId(),
            'name' => $tagGroup->getName(),
            'createTime' => $tagGroup->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $tagGroup->getUpdateTime()?->format('Y-m-d H:i:s'),
            'createdBy' => $tagGroup->getCreatedBy(),
            'updatedBy' => $tagGroup->getUpdatedBy(),
            'tagCount' => $tagGroup->getTags()->count(),
        ];

        if ($param->includeTags) {
            $tags = [];
            foreach ($tagGroup->getTags() as $tag) {
                $tags[] = [
                    'id' => $tag->getId(),
                    'name' => $tag->getName(),
                    'valid' => $tag->isValid(),
                    'createTime' => $tag->getCreateTime()?->format('Y-m-d H:i:s'),
                ];
            }
            $result['tags'] = $tags;
        }

        return new ArrayResult($result);
    }

    public function getCacheKey(JsonRpcRequest $request): string
    {
        $params = $request->getParams();
        if (null === $params) {
            throw new TagManageException('Parameters cannot be null');
        }

        return $this->buildParamCacheKey($params);
    }

    public function getCacheDuration(JsonRpcRequest $request): int
    {
        return 600; // 10分钟
    }

    /**
     * @return iterable<string>
     */
    public function getCacheTags(JsonRpcRequest $request): iterable
    {
        $params = $request->getParams();
        $groupId = $params?->get('groupId') ?? '';

        return ['tag_group', 'tag_group_detail', 'tag_group_' . $groupId];
    }

    /**
     * @return array<string, mixed>
     */
}
