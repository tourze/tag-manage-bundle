<?php

namespace Tourze\TagManageBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\TagManageBundle\DependencyInjection\TagManageExtension;

/**
 * @internal
 */
#[CoversClass(TagManageExtension::class)]
final class TagManageExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
}
