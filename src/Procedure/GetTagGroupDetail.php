<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Procedure;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPCCacheBundle\Procedure\CacheableProcedure;
use Tourze\TagManageBundle\Exception\TagManageException;
use Tourze\TagManageBundle\Repository\TagGroupRepository;

#[MethodTag(name: '标签管理')]
#[MethodDoc(summary: '获取标签组详细信息')]
#[MethodExpose(method: 'GetTagGroupDetail')]
final class GetTagGroupDetail extends CacheableProcedure
{
    #[MethodParam(description: '标签组ID')]
    #[Assert\NotBlank]
    public string $groupId;

    #[MethodParam(description: '是否包含关联的标签列表')]
    public bool $includeTags = false;

    public function __construct(
        private readonly TagGroupRepository $tagGroupRepository,
    ) {
    }

    public function execute(): array
    {
        $tagGroup = $this->tagGroupRepository->find($this->groupId);
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

        if ($this->includeTags) {
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

        return $result;
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
        return ['tag_group', 'tag_group_detail', 'tag_group_' . $this->groupId];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getMockResult(): array
    {
        return [
            'id' => '1',
            'name' => '推荐标签',
            'createTime' => '2024-01-01 12:00:00',
            'updateTime' => '2024-01-01 12:00:00',
            'createdBy' => 'admin',
            'updatedBy' => 'admin',
            'tagCount' => 3,
            'tags' => [
                [
                    'id' => '101',
                    'name' => '热门',
                    'valid' => true,
                    'createTime' => '2024-01-01 12:10:00',
                ],
                [
                    'id' => '102',
                    'name' => '推荐',
                    'valid' => true,
                    'createTime' => '2024-01-01 12:15:00',
                ],
            ],
        ];
    }
}
