<?php
namespace Mas\Ali;

class Router {
    private static $routes = [];
    private static $notFound = null;
    private static $basePath = '';
    
    public static function setBasePath($path) {
        self::$basePath = rtrim($path, '/');
    }
    
    public static function add($method, $path, $handler) {
        self::$routes[strtoupper($method)][$path] = $handler;
    }
    
    public static function get($path, $handler) {
        self::add('GET', $path, $handler);
    }
    
    public static function post($path, $handler) {
        self::add('POST', $path, $handler);
    }
    
    public static function put($path, $handler) {
        self::add('PUT', $path, $handler);
    }
    
    public static function delete($path, $handler) {
        self::add('DELETE', $path, $handler);
    }
    
    public static function notFound($handler) {
        self::$notFound = $handler;
    }
    
    public static function run() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path
        if (self::$basePath && strpos($uri, self::$basePath) === 0) {
            $uri = substr($uri, strlen(self::$basePath));
        }
        
        $uri = rtrim($uri, '/') ?: '/';
        
        // Cek route
        if (isset(self::$routes[$method])) {
            foreach (self::$routes[$method] as $path => $handler) {
                $pattern = self::patternToRegex($path);
                
                if (preg_match($pattern, $uri, $matches)) {
                    array_shift($matches);
                    return self::callHandler($handler, $matches);
                }
            }
        }
        
        // 404
        if (self::$notFound) {
            return self::callHandler(self::$notFound, []);
        }
        
        http_response_code(404);
        echo "404 - Halaman tidak ditemukan";
    }
    
    private static function patternToRegex($path) {
        $pattern = preg_replace('/\{[^\}]+\}/', '([^/]+)', $path);
        return "#^$pattern$#";
    }
    
    private static function callHandler($handler, $params) {
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);
            
            // Coba dengan namespace App\Controllers
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
