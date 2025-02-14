<?php

namespace Tkhamez\Tests\Slim\RoleAuth;

use PHPUnit\Framework\TestCase;
use Tkhamez\Slim\RoleAuth\RoleMiddleware;
use Tkhamez\Slim\RoleAuth\RoleProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\RouteInterface;

class RoleMiddlewareTest extends TestCase
{
    public function testAddsRolesForPaths()
    {
        $test = $this;
        $next = function (ServerRequestInterface $req) use ($test) {
            $test->assertSame(['r1', 'r2'], $req->getAttribute('roles'));
            return new Response();
        };

        $this->invokeMiddleware('/path1', ['/path1', '/path2'], ['r1', 'r2'], $next, true);
        $this->invokeMiddleware('/path23/4', ['/path1', '/path2'], ['r1', 'r2'], $next, true);
    }

    public function testDoesNotAddRolesForOtherPaths()
    {
        $test = $this;
        $next = function (ServerRequestInterface $req) use ($test) {
            $test->assertNull($req->getAttribute('roles'));
            return new Response();
        };

        $this->invokeMiddleware('/other/path', ['/path1'], ['role1'], $next, true);
        $this->invokeMiddleware('/not/path1', ['/path1'], ['role1'], $next, true);
    }

    public function testAddsRolesWithoutPaths()
    {
        $test = $this;
        $next = function (ServerRequestInterface $req) use ($test) {
            $test->assertSame(['role1'], $req->getAttribute('roles'));
            return new Response();
        };

        $this->invokeMiddleware('/path1', null, ['role1'], $next, true);
        $this->invokeMiddleware('/path1', [], ['role1'], $next, true);
    }

    public function testAddsRolesWithoutRouteAttribute()
    {
        $test = $this;
        $next = function (ServerRequestInterface $req) use ($test) {
            $test->assertSame(['role1'], $req->getAttribute('roles'));
            return new Response();
        };

        $this->invokeMiddleware('/path1', ['/path1'], ['role1'], $next, false);
    }

    private function invokeMiddleware($path, $routes, $roles, $next, $addRoute)
    {
        $request = Request::createFromEnvironment(Environment::mock());

        $route = $this->getMockBuilder(RouteInterface::class)->getMock();
        $route->method('getPattern')->willReturn($path);
        if ($addRoute) {
            $request = $request->withAttribute('route', $route);
        }

        /* @var $roleProvider RoleProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
        $roleProvider = $this->getMockBuilder(RoleProviderInterface::class)->getMock();
        $roleProvider->method('getRoles')->willReturn($roles);

        $roleMiddleware = new RoleMiddleware($roleProvider, ['route_pattern' =>  $routes]);

        $roleMiddleware($request, new Response(), $next);
    }
}
