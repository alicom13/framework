<?php

namespace Mas\Ali;

class Router
{
    private static $routes = [];
    private static $filters = [];
    private static $notFound;

    public static function get($path, $handler, $filters = [])
    {
        self::$routes['GET'][] = [
            'path' => $path,
            'handler' => $handler,
            'filters' => $filters
        ];
    }

    public static function post($path, $handler, $filters = [])
    {
        self::$routes['POST'][] = [
            'path' => $path,
            'handler' => $handler,
            'filters' => $filters
        ];
    }

    public static function put($path, $handler, $filters = [])
    {
        self::$routes['PUT'][] = [
            'path' => $path,
            'handler' => $handler,
            'filters' => $filters
        ];
    }

    public static function delete($path, $handler, $filters = [])
    {
        self::$routes['DELETE'][] = [
            'path' => $path,
            'handler' => $handler,
            'filters' => $filters
        ];
    }

    public static function notFound($handler)
    {
        self::$notFound = $handler;
    }

    public static function addFilter($filter)
    {
        self::$filters[] = $filter;
    }

    public static function run()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        $routeMatched = false;

        if (isset(self::$routes[$method])) {
            foreach (self::$routes[$method] as $route) {
                $pattern = self::patternToRegex($route['path']);
                if (preg_match($pattern, $uri, $matches)) {
                    $routeMatched = true;
                    array_shift($matches);

                    foreach (self::$filters as $filter) {
                        if (class_exists($filter)) {
                            $filter::before();
                        }
                    }

                    foreach ($route['filters'] as $filter) {
                        if (class_exists($filter)) {
                            $filter::before();
                        }
                    }

                    $response = self::callHandler($route['handler'], $matches);

                    foreach ($route['filters'] as $filter) {
                        if (class_exists($filter)) {
                            $filter::after();
                        }
                    }

                    foreach (self::$filters as $filter) {
                        if (class_exists($filter)) {
                            $filter::after();
                        }
                    }

                    return $response;
                }
            }
        }

        if (!$routeMatched) {
            if (self::$notFound) {
                return self::callHandler(self::$notFound, []);
            }
            http_response_code(404);
            echo "404 - Halaman tidak ditemukan";
        }
    }

    private static function patternToRegex($path)
    {
        $pattern = preg_replace('/\{[^\}]+\}/', '([^/]+)', $path);
        return "#^$pattern$#";
    }

    private static function callHandler($handler, $params)
    {
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);
            $controller = "App\\Controllers\\$controller";
            if (class_exists($controller)) {
                $obj = new $controller();
                return $obj->$method(...$params);
            }
            throw new \Exception("Controller '$controller' tidak ditemukan");
        }

        if (is_callable($handler)) {
            return $handler(...$params);
        }

        return $handler;
    }
}
