<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Procedure;

use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPCCacheBundle\Procedure\CacheableProcedure;
use Tourze\TagManageBundle\Entity\TagGroup;
use Tourze\TagManageBundle\Exception\TagManageException;
use Tourze\TagManageBundle\Param\GetTagGroupListParam;
use Tourze\TagManageBundle\Repository\TagGroupRepository;

#[MethodTag(name: '标签管理')]
#[MethodDoc(summary: '获取标签组列表')]
#[MethodExpose(method: 'GetTagGroupList')]
final class GetTagGroupList extends CacheableProcedure
{
    public function __construct(
        private readonly TagGroupRepository $tagGroupRepository,
    ) {
    }

    /**
     * @phpstan-param GetTagGroupListParam $param
     */
    public function execute(GetTagGroupListParam|RpcParamInterface $param): ArrayResult
    {
        $offset = ($param->page - 1) * $param->limit;

        $qb = $this->tagGroupRepository->createQueryBuilder('tg')
            ->orderBy('tg.createTime', 'DESC')
        ;

        if (null !== $param->keyword) {
            $qb->andWhere('tg.name LIKE :keyword')
                ->setParameter('keyword', '%' . $param->keyword . '%')
            ;
        }

        // 获取总数
        $totalCount = (clone $qb)
            ->select('COUNT(tg.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        // 确保 totalCount 是数字类型
        $totalCount = (int) $totalCount;

        // 获取分页数据
        /** @var array<TagGroup> $tagGroups */
        $tagGroups = $qb
            ->setFirstResult($offset)
            ->setMaxResults($param->limit)
            ->getQuery()
            ->getResult()
        ;

        $items = [];
        foreach ($tagGroups as $tagGroup) {
            $item = [
                'id' => $tagGroup->getId(),
                'name' => $tagGroup->getName(),
                'createTime' => $tagGroup->getCreateTime()?->format('Y-m-d H:i:s'),
                'updateTime' => $tagGroup->getUpdateTime()?->format('Y-m-d H:i:s'),
                'createdBy' => $tagGroup->getCreatedBy(),
                'updatedBy' => $tagGroup->getUpdatedBy(),
            ];

            if ($param->withTagCount) {
                $item['tagCount'] = $tagGroup->getTags()->count();
            }

            $items[] = $item;
        }

        return new ArrayResult([
            'items' => $items,
            'pagination' => [
                'page' => $param->page,
                'limit' => $param->limit,
                'total' => $totalCount,
                'totalPages' => $param->limit > 0 ? (int) ceil($totalCount / $param->limit) : 0,
            ],
        ]);
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
        return 300; // 5分钟
    }

    /**
     * @return iterable<string>
     */
    public function getCacheTags(JsonRpcRequest $request): iterable
    {
        return ['tag_group', 'tag_group_list'];
    }

    /**
     * @return array<string, mixed>
     */
}
