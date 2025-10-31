<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Procedure;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPCCacheBundle\Procedure\CacheableProcedure;
use Tourze\TagManageBundle\Entity\TagGroup;
use Tourze\TagManageBundle\Exception\TagManageException;
use Tourze\TagManageBundle\Repository\TagGroupRepository;

#[MethodTag(name: '标签管理')]
#[MethodDoc(summary: '获取标签组列表')]
#[MethodExpose(method: 'GetTagGroupList')]
final class GetTagGroupList extends CacheableProcedure
{
    #[MethodParam(description: '页码')]
    #[Assert\PositiveOrZero]
    public int $page = 1;

    #[MethodParam(description: '每页数量')]
    #[Assert\Range(min: 1, max: 100)]
    public int $limit = 20;

    #[MethodParam(description: '搜索关键字')]
    #[Assert\Length(max: 100)]
    public ?string $keyword = null;

    #[MethodParam(description: '是否包含标签统计')]
    public bool $withTagCount = false;

    public function __construct(
        private readonly TagGroupRepository $tagGroupRepository,
    ) {
    }

    public function execute(): array
    {
        $offset = ($this->page - 1) * $this->limit;

        $qb = $this->tagGroupRepository->createQueryBuilder('tg')
            ->orderBy('tg.createTime', 'DESC')
        ;

        if (null !== $this->keyword) {
            $qb->andWhere('tg.name LIKE :keyword')
                ->setParameter('keyword', '%' . $this->keyword . '%')
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
            ->setMaxResults($this->limit)
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

            if ($this->withTagCount) {
                $item['tagCount'] = $tagGroup->getTags()->count();
            }

            $items[] = $item;
        }

        return [
            'items' => $items,
            'pagination' => [
                'page' => $this->page,
                'limit' => $this->limit,
                'total' => $totalCount,
                'totalPages' => $this->limit > 0 ? (int) ceil($totalCount / $this->limit) : 0,
            ],
        ];
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
    public static function getMockResult(): array
    {
        return [
            'items' => [
                [
                    'id' => '1',
                    'name' => '推荐标签',
                    'createTime' => '2024-01-01 12:00:00',
                    'updateTime' => '2024-01-01 12:00:00',
                    'createdBy' => 'admin',
                    'updatedBy' => 'admin',
                    'tagCount' => 5,
                ],
                [
                    'id' => '2',
                    'name' => '热门标签',
                    'createTime' => '2024-01-01 13:00:00',
                    'updateTime' => '2024-01-01 13:00:00',
                    'createdBy' => 'admin',
                    'updatedBy' => 'admin',
                    'tagCount' => 8,
                ],
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 20,
                'total' => 2,
                'totalPages' => 1,
            ],
        ];
    }
}
