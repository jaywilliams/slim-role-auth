<?php

namespace Tkhamez\Slim\RoleAuth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RouteInterface;

/**
 * Denies access to a route if the required role is missing.
 *
 * Sends a HTTP status 403 (default) or optionally a "Location"
 * header for a redirect.
 *
 * It loads the roles from the request attribute named "roles"
 * (an array with string values, e. g. ['role.one', 'role.two']).
 *
 * The role attribute is provided by the RoleMiddleware class.
 *
 * The configured routes are compared against the route pattern from a
 * RouteInterface class from the request attribute named "route"
 * (provided by Slim).
 *
 * All routes are *allowed* if the "route" attributes is missing in the request object!
 */
class SecureRouteMiddleware
{
    /**
     * @var array
     */
    private $secured;

    /**
     * @var array
     */
    private $options;

    /**
     * Constructor.
     *
     * Parameter secured:
     * - Example: ['/secured/public' => ['anonymous', 'user'], '/secured' => ['user']]
     * - First match will be used.
     * - Keys are route pattern, matched by "starts-with".
     * - Values are roles, only one must match to allow the route.
     *
     * Parameter options:
     * - Example: ['redirect_url' => '/login']
     * - redirect_url: send a Location header instead of a 403 status code.
     *
     * @param array $secured
     * @param array $options
     */
    public function __construct(array $secured, array $options = [])
    {
        $this->secured = $secured;
        $this->options = $options;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $route = $request->getAttribute('route');
        if (! $route instanceof RouteInterface) {
            return $next($request, $response);
        }

        $roles = $request->getAttribute('roles');
        $routePattern = $route->getPattern();

        $allowed = true;
        foreach ($this->secured as $securedRoute => $requiredRoles) {
            if (strpos($routePattern, $securedRoute) !== 0) {
                continue;
            }
            if (! is_array($roles) || count(array_intersect($requiredRoles, $roles)) === 0) {
                $allowed = false;
            }
            break;
        }

        if ($allowed === false) {
            if (isset($this->options['redirect_url'])) {
                return $response->withHeader('Location', (string)$this->options['redirect_url']);
            } else {
                return $response->withStatus(403);
            }
        }

        return $next($request, $response);
    }
}
