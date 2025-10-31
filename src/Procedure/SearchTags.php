<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Procedure;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\TagManageBundle\Entity\Tag;
use Tourze\TagManageBundle\Repository\TagRepository;

#[MethodTag(name: '标签管理')]
#[MethodDoc(summary: '搜索标签')]
#[MethodExpose(method: 'SearchTags')]
final class SearchTags extends BaseProcedure
{
    #[MethodParam(description: '搜索关键词')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 50)]
    public string $keyword;

    #[MethodParam(description: '最大返回数量')]
    #[Assert\Range(min: 1, max: 50)]
    public int $limit = 10;

    #[MethodParam(description: '是否只搜索有效标签')]
    public bool $validOnly = true;

    #[MethodParam(description: '标签组ID过滤')]
    public ?string $groupId = null;

    #[MethodParam(description: '是否按使用频率排序')]
    public bool $orderByUsage = false;

    public function __construct(
        private readonly TagRepository $tagRepository,
    ) {
    }

    public function execute(): array
    {
        if (mb_strlen($this->keyword) < 1) {
            throw new ApiException('搜索关键词不能为空');
        }

        // 构建查询
        $qb = $this->tagRepository->createQueryBuilder('t')
            ->where('t.name LIKE :keyword')
            ->setParameter('keyword', '%' . $this->keyword . '%')
            ->setMaxResults($this->limit)
        ;

        // 添加筛选条件
        if ($this->validOnly) {
            $qb->andWhere('t.valid = :valid')
                ->setParameter('valid', true)
            ;
        }

        if (null !== $this->groupId && '' !== $this->groupId) {
            $qb->andWhere('t.groups = :groupId')
                ->setParameter('groupId', $this->groupId)
            ;
        }

        // 排序
        if ($this->orderByUsage) {
            // 实际应该按使用统计排序，这里临时用ID
            $qb->orderBy('t.id', 'DESC');
        } else {
            // 按匹配度排序：优先精确匹配，然后按名称长度
            $qb->addSelect('CASE WHEN t.name = :exactKeyword THEN 0 ELSE LENGTH(t.name) END as HIDDEN matchOrder')
                ->setParameter('exactKeyword', $this->keyword)
                ->orderBy('matchOrder', 'ASC')
                ->addOrderBy('t.name', 'ASC')
            ;
        }

        /** @var array<Tag> $tags */
        $tags = $qb->getQuery()->getResult();

        return [
            'keyword' => $this->keyword,
            'total' => count($tags),
            'tags' => array_map(fn (Tag $tag) => [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
                'valid' => $tag->isValid(),
                'group' => null !== $tag->getGroups() ? [
                    'id' => $tag->getGroups()->getId(),
                    'name' => $tag->getGroups()->getName(),
                ] : null,
                'highlighted' => $this->highlightKeyword($tag->getName() ?? '', $this->keyword),
            ], $tags),
        ];
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
    public static function getMockResult(): array
    {
        return [
            'keyword' => '科技',
            'total' => 3,
            'tags' => [
                [
                    'id' => 1,
                    'name' => '科技',
                    'valid' => true,
                    'group' => [
                        'id' => '1',
                        'name' => '分类标签',
                    ],
                    'highlighted' => '<mark>科技</mark>',
                ],
                [
                    'id' => 2,
                    'name' => '科技产品',
                    'valid' => true,
                    'group' => [
                        'id' => '1',
                        'name' => '分类标签',
                    ],
                    'highlighted' => '<mark>科技</mark>产品',
                ],
                [
                    'id' => 3,
                    'name' => '高科技',
                    'valid' => true,
                    'group' => [
                        'id' => '2',
                        'name' => '特色标签',
                    ],
                    'highlighted' => '高<mark>科技</mark>',
                ],
            ],
        ];
    }
}
