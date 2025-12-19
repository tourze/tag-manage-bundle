<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Procedure;

use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Param\SearchTagsParam;
use Tourze\TagManageBundle\Repository\TagRepository;

#[MethodTag(name: '标签管理')]
#[MethodDoc(summary: '搜索标签')]
#[MethodExpose(method: 'SearchTags')]
final class SearchTags extends BaseProcedure
{
    public function __construct(
        private readonly TagRepository $tagRepository,
    ) {
    }

    /**
     * @phpstan-param SearchTagsParam $param
     */
    public function execute(SearchTagsParam|RpcParamInterface $param): ArrayResult
    {
        if (mb_strlen($param->keyword) < 1) {
            throw new ApiException('搜索关键词不能为空');
        }

        // 构建查询
        $qb = $this->tagRepository->createQueryBuilder('t')
            ->where('t.name LIKE :keyword')
            ->setParameter('keyword', '%' . $param->keyword . '%')
            ->setMaxResults($param->limit)
        ;

        // 添加筛选条件
        if ($param->validOnly) {
            $qb->andWhere('t.valid = :valid')
                ->setParameter('valid', true)
            ;
        }

        if (null !== $param->groupId && '' !== $param->groupId) {
            $qb->andWhere('t.groups = :groupId')
                ->setParameter('groupId', $param->groupId)
            ;
        }

        // 排序
        if ($param->orderByUsage) {
            // 实际应该按使用统计排序，这里临时用ID
            $qb->orderBy('t.id', 'DESC');
        } else {
            // 按匹配度排序：优先精确匹配，然后按名称长度
            $qb->addSelect('CASE WHEN t.name = :exactKeyword THEN 0 ELSE LENGTH(t.name) END as HIDDEN matchOrder')
                ->setParameter('exactKeyword', $param->keyword)
                ->orderBy('matchOrder', 'ASC')
                ->addOrderBy('t.name', 'ASC')
            ;
        }

        /** @var array<Tag> $tags */
        $tags = $qb->getQuery()->getResult();

        return new ArrayResult([
            'keyword' => $param->keyword,
            'total' => count($tags),
            'tags' => array_map(fn (Tag $tag) => [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
                'valid' => $tag->isValid(),
                'group' => null !== $tag->getGroups() ? [
                    'id' => $tag->getGroups()->getId(),
                    'name' => $tag->getGroups()->getName(),
                ] : null,
                'highlighted' => $this->highlightKeyword($tag->getName() ?? '', $param->keyword),
            ], $tags),
        ]);
    }

    /**
     * 高亮关键词
     */
    private function highlightKeyword(string $text, string $keyword): string
    {
        $result = preg_replace(
            '/(' . preg_quote($keyword, '/') . ')/ui',
            '<mark>$1</mark>',
            $text
        );

        return null !== $result ? $result : $text;
    }

    /**
     * @return array<string, mixed>
     */
}
