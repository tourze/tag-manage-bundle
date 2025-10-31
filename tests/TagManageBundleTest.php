<?php

namespace Tourze\TagManageBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Knp\Bundle\PaginatorBundle\KnpPaginatorBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineIpBundle\DoctrineIpBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\DoctrineTrackBundle\DoctrineTrackBundle;
use Tourze\DoctrineUserBundle\DoctrineUserBundle;
use Tourze\JsonRPCPaginatorBundle\JsonRPCPaginatorBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\TagManageBundle\TagManageBundle;

/**
 * @internal
 */
#[CoversClass(TagManageBundle::class)]
#[RunTestsInSeparateProcesses]
final class TagManageBundleTest extends AbstractBundleTestCase
{
    public function testGetBundleDependencies(): void
    {
        $dependencies = TagManageBundle::getBundleDependencies();

        $expectedDependencies = [
            DoctrineBundle::class => ['all' => true],
            KnpPaginatorBundle::class => ['all' => true],
            JsonRPCPaginatorBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            DoctrineIpBundle::class => ['all' => true],
            DoctrineTrackBundle::class => ['all' => true],
            DoctrineTimestampBundle::class => ['all' => true],
            DoctrineUserBundle::class => ['all' => true],
        ];

        $this->assertSame($expectedDependencies, $dependencies);
    }
}
