<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Procedure;

use Doctrine\ORM\QueryBuilder;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Model\JsonRpcParams;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPCCacheBundle\Procedure\CacheableProcedure;
use Tourze\JsonRPCPaginatorBundle\Procedure\PaginatorTrait;
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Param\GetTagListParam;
use Tourze\TagManageBundle\Repository\TagGroupRepository;
use Tourze\TagManageBundle\Repository\TagRepository;

#[MethodTag(name: '标签管理')]
#[MethodDoc(summary: '获取标签列表')]
#[MethodExpose(method: 'GetTagList')]
final class GetTagList extends CacheableProcedure
{
    use PaginatorTrait;

    public function __construct(
        private readonly TagRepository $tagRepository,
        private readonly TagGroupRepository $tagGroupRepository,
    ) {
    }

    /**
     * @phpstan-param GetTagListParam $param
     */
    public function execute(GetTagListParam|RpcParamInterface $param): ArrayResult
    {
        $this->validateTagGroup($param);
        $qb = $this->buildQuery($param);

        return new ArrayResult($this->fetchList($qb, fn ($tag) => $this->formatTagData($tag, $param), null, $param));
    }

    private function validateTagGroup(GetTagListParam $param): void
    {
        if (null === $param->groupId || '' === $param->groupId) {
            return;
        }

        $tagGroup = $this->tagGroupRepository->find($param->groupId);
        if (null === $tagGroup) {
            throw new ApiException('标签组不存在');
        }
    }

    private function buildQuery(GetTagListParam $param): QueryBuilder
    {
        $qb = $this->tagRepository->createQueryBuilder('t')
            ->leftJoin('t.groups', 'g')
        ;

        $this->applySorting($qb, $param);
        $this->applyFilters($qb, $param);

        return $qb;
    }

    private function applySorting(QueryBuilder $qb, GetTagListParam $param): void
    {
        if ('usage' === $param->orderBy) {
            // 假设有使用统计字段，实际需要根据业务需求调整
            $qb->orderBy('t.id', $param->orderDir); // 临时用ID排序，实际应该是使用统计
        } else {
            $qb->orderBy('t.' . $param->orderBy, $param->orderDir);
        }
    }

    private function applyFilters(QueryBuilder $qb, GetTagListParam $param): void
    {
        if (null !== $param->groupId && '' !== $param->groupId) {
            $qb->andWhere('t.groups = :groupId')
                ->setParameter('groupId', $param->groupId)
            ;
        }

        if ($param->validOnly) {
            $qb->andWhere('t.valid = :valid')
                ->setParameter('valid', true)
            ;
        }

        if (null !== $param->keyword && '' !== $param->keyword) {
            $qb->andWhere('t.name LIKE :keyword')
                ->setParameter('keyword', '%' . $param->keyword . '%')
            ;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTagData(Tag $tag, GetTagListParam $param): array
    {
        $data = [
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'valid' => $tag->isValid(),
            'group' => null !== $tag->getGroups() ? [
                'id' => $tag->getGroups()->getId(),
                'name' => $tag->getGroups()->getName(),
            ] : null,
            'createTime' => $tag->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $tag->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];

        // 包含使用统计（需要根据实际业务调整）
        if ($param->includeUsageStats) {
            $data['usageCount'] = 0; // 实际应该查询关联实体的数量
            $data['lastUsedTime'] = null;
        }

        return $data;
    }

    public function getCacheKey(JsonRpcRequest $request): string
    {
        $params = $request->getParams();

        return $this->buildParamCacheKey($params ?? new JsonRpcParams());
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
        $tags = ['tag', 'tag_list'];
        $params = $request->getParams();
        $groupId = $params?->get('groupId');
        if (null !== $groupId && '' !== $groupId) {
            $tags[] = 'tag_group_' . $groupId;
        }

        return $tags;
    }

    /**
     * @return array<string, mixed>
     */
}
