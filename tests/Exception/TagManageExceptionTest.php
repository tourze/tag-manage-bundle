<?php

declare(strict_types=1);

namespace Tourze\TagManageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TagManageBundle\Exception\TagManageException;

/**
 * @internal
 */
#[CoversClass(TagManageException::class)]
final class TagManageExceptionTest extends AbstractExceptionTestCase
{
    protected function createException(string $message = 'test message', int $code = 0, ?\Throwable $previous = null): \Exception
    {
        return new TagManageException($message, $code, $previous);
    }
}
