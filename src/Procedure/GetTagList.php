<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Procedure;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Model\JsonRpcParams;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPCCacheBundle\Procedure\CacheableProcedure;
use Tourze\JsonRPCPaginatorBundle\Procedure\PaginatorTrait;
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Repository\TagGroupRepository;
use Tourze\TagManageBundle\Repository\TagRepository;

#[MethodTag(name: '标签管理')]
#[MethodDoc(summary: '获取标签列表')]
#[MethodExpose(method: 'GetTagList')]
final class GetTagList extends CacheableProcedure
{
    use PaginatorTrait;

    #[MethodParam(description: '标签组ID')]
    public ?string $groupId = null;

    #[MethodParam(description: '搜索关键词')]
    public ?string $keyword = null;

    #[MethodParam(description: '是否只获取有效的标签')]
    public bool $validOnly = true;

    #[MethodParam(description: '排序字段')]
    #[Assert\Choice(choices: ['name', 'createTime', 'updateTime', 'usage'])]
    public string $orderBy = 'createTime';

    #[MethodParam(description: '排序方向')]
    #[Assert\Choice(choices: ['ASC', 'DESC'])]
    public string $orderDir = 'DESC';

    #[MethodParam(description: '是否包含使用统计')]
    public bool $includeUsageStats = false;

    public function __construct(
        private readonly TagRepository $tagRepository,
        private readonly TagGroupRepository $tagGroupRepository,
    ) {
    }

    public function execute(): array
    {
        $this->validateTagGroup();
        $qb = $this->buildQuery();

        return $this->fetchList($qb, fn ($tag) => $this->formatTagData($tag));
    }

    private function validateTagGroup(): void
    {
        if (null === $this->groupId || '' === $this->groupId) {
            return;
        }

        $tagGroup = $this->tagGroupRepository->find($this->groupId);
        if (null === $tagGroup) {
            throw new ApiException('标签组不存在');
        }
    }

    private function buildQuery(): QueryBuilder
    {
        $qb = $this->tagRepository->createQueryBuilder('t')
            ->leftJoin('t.groups', 'g')
        ;

        $this->applySorting($qb);
        $this->applyFilters($qb);

        return $qb;
    }

    private function applySorting(QueryBuilder $qb): void
    {
        if ('usage' === $this->orderBy) {
            // 假设有使用统计字段，实际需要根据业务需求调整
            $qb->orderBy('t.id', $this->orderDir); // 临时用ID排序，实际应该是使用统计
        } else {
            $qb->orderBy('t.' . $this->orderBy, $this->orderDir);
        }
    }

    private function applyFilters(QueryBuilder $qb): void
    {
        if (null !== $this->groupId && '' !== $this->groupId) {
            $qb->andWhere('t.groups = :groupId')
                ->setParameter('groupId', $this->groupId)
            ;
        }

        if ($this->validOnly) {
            $qb->andWhere('t.valid = :valid')
                ->setParameter('valid', true)
            ;
        }

        if (null !== $this->keyword && '' !== $this->keyword) {
            $qb->andWhere('t.name LIKE :keyword')
                ->setParameter('keyword', '%' . $this->keyword . '%')
            ;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTagData(Tag $tag): array
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
        if ($this->includeUsageStats) {
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
        if (null !== $this->groupId && '' !== $this->groupId) {
            $tags[] = 'tag_group_' . $this->groupId;
        }

        return $tags;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getMockResult(): array
    {
        return [
            'list' => [
                [
                    'id' => 1,
                    'name' => '热门',
                    'valid' => true,
                    'group' => [
                        'id' => '1',
                        'name' => '推荐标签',
                    ],
                    'usageCount' => 156,
                    'lastUsedTime' => '2024-01-15 10:30:00',
                    'createTime' => '2024-01-01 12:00:00',
                    'updateTime' => '2024-01-01 12:00:00',
                ],
                [
                    'id' => 2,
                    'name' => '科技',
                    'valid' => true,
                    'group' => [
                        'id' => '2',
                        'name' => '分类标签',
                    ],
                    'usageCount' => 89,
                    'lastUsedTime' => '2024-01-14 15:20:00',
                    'createTime' => '2024-01-01 12:00:00',
                    'updateTime' => '2024-01-01 12:00:00',
                ],
            ],
            'pagination' => [
                'current' => 1,
                'pageSize' => 20,
                'total' => 2,
                'hasMore' => false,
            ],
        ];
    }
}
