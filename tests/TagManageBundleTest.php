<?php

namespace Tourze\TagManageBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\TagManageBundle\TagManageBundle;

/**
 * @internal
 */
#[CoversClass(TagManageBundle::class)]
#[RunTestsInSeparateProcesses]
final class TagManageBundleTest extends AbstractBundleTestCase
{
}
