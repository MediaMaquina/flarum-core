<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Tests\integration\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareTest extends TestCase
{
    // This adds the first custom middleware for test that require a middleware to already exist
    private function add_first_middleware()
    {
        $this->extend(
            (new Extend\Middleware('forum'))->add(FirstTestMiddleware::class)
        );
    }

    /**
     * @test
     */
    public function can_add_middleware()
    {
        $this->extend(
            (new Extend\Middleware('forum'))->add(FirstTestMiddleware::class)
        );

        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('X-First-Test-Middleware', $response->getHeaders());
    }

    /**
     * @test
     */
    public function can_replace_middleware()
    {
        $this->add_first_middleware();
        $this->extend(
            (new Extend\Middleware('forum'))->replace(FirstTestMiddleware::class, SecondTestMiddleware::class)
        );

        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayNotHasKey('X-First-Test-Middleware', $response->getHeaders());
        $this->assertArrayHasKey('X-Second-Test-Middleware', $response->getHeaders());
    }

    /**
     * @test
     */
    public function can_remove_middleware()
    {
        $this->add_first_middleware();
        $this->extend(
            (new Extend\Middleware('forum'))->remove(FirstTestMiddleware::class)
        );

        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayNotHasKey('X-First-Test-Middleware', $response->getHeaders());
    }
}

class FirstTestMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response->withAddedHeader('X-First-Test-Middleware', 'This is a test!');
    }
}

class SecondTestMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response->withAddedHeader('X-Second-Test-Middleware', 'This is another test!');
    }
}
