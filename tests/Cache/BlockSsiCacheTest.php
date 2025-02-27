<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\PageBundle\Tests\Page;

use PHPUnit\Framework\TestCase;
use Sonata\BlockBundle\Block\BlockContextManagerInterface;
use Sonata\BlockBundle\Block\BlockRendererInterface;
use Sonata\Cache\CacheElement;
use Sonata\PageBundle\Cache\BlockSsiCache;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Routing\RouterInterface;

final class BlockSsiCacheTest extends TestCase
{
    /**
     * @dataProvider      getExceptionCacheKeys
     */
    public function testExceptions($keys): void
    {
        $this->expectException(\RuntimeException::class);

        $router = $this->createMock(RouterInterface::class);
        $resolver = $this->createMock(ControllerResolverInterface::class);
        $argumentResolver = $this->createMock(ArgumentResolverInterface::class);
        $blockRenderer = $this->createMock(BlockRendererInterface::class);
        $contextManager = $this->createMock(BlockContextManagerInterface::class);

        $cache = new BlockSsiCache('', $router, $resolver, $argumentResolver, $blockRenderer, $contextManager);

        $cache->get($keys);
    }

    public static function getExceptionCacheKeys(): array
    {
        return [
            [[]],
            [['block_id' => 7]],
            [['block_id' => 7, 'page_id' => 8]],
            [['block_id' => 7, 'manager' => 8]],
            [['manager' => 7, 'page_id' => 8]],
            [['manager' => 7, 'page_id' => 8]],
            [['updated_at' => 'foo']],
        ];
    }

    public function testInitCache(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturn('/cache/page/esi/XXXXX/page/5/4?updated_at=as');

        $resolver = $this->createMock(ControllerResolverInterface::class);
        $argumentResolver = $this->createMock(ArgumentResolverInterface::class);
        $blockRenderer = $this->createMock(BlockRendererInterface::class);
        $contextManager = $this->createMock(BlockContextManagerInterface::class);

        $cache = new BlockSsiCache('', $router, $resolver, $argumentResolver, $blockRenderer, $contextManager);

        static::assertTrue($cache->flush([]));
        static::assertTrue($cache->flushAll());

        $keys = [
            'block_id' => 4,
            'page_id' => 5,
            'updated_at' => 'as',
            'manager' => 'page',
        ];

        $cacheElement = $cache->set($keys, 'data');

        static::assertInstanceOf(CacheElement::class, $cacheElement);

        static::assertTrue($cache->has(['id' => 7]));

        $cacheElement = $cache->get($keys);

        static::assertInstanceOf(CacheElement::class, $cacheElement);

        static::assertSame(
            '<!--# include virtual="/cache/page/esi/XXXXX/page/5/4?updated_at=as" -->',
            $cacheElement->getData()->getContent()
        );
    }
}
