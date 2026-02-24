<?php

use Mas\Ali\Database;
use Mas\Ali\Framework;

if (!function_exists('app')) {
    function app() {
        return Framework::getInstance();
    }
}

if (!function_exists('config')) {
    function config($key = null, $default = null) {
        return app()->config($key, $default);
    }
}

if (!function_exists('db')) {
    function db($name = 'default') {
        return Database::connection($name);
    }
}

if (!function_exists('query')) {
    function query($sql, $params = [], $connection = 'default') {
        $conn = Database::connection($connection);
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

if (!function_exists('view')) {
    function view($name, $data = []) {
        extract($data);
        
        $path = dirname(__DIR__, 4) . '/views/' . $name . '.php';
        
        if (file_exists($path)) {
            require $path;
            return;
        }
        
        throw new Exception("View '$name' tidak ditemukan di $path");
    }
}

if (!function_exists('json')) {
    function json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

if (!function_exists('back')) {
    function back() {
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
}

if (!function_exists('get')) {
    function get($path, $handler) {
        \Mas\Ali\Router::get($path, $handler);
    }
}

if (!function_exists('post')) {
    function post($path, $handler) {
        \Mas\Ali\Router::post($path, $handler);
    }
}

if (!function_exists('put')) {
    function put($path, $handler) {
        \Mas\Ali\Router::put($path, $handler);
    }
}

if (!function_exists('delete')) {
    function delete($path, $handler) {
        \Mas\Ali\Router::delete($path, $handler);
    }
}

if (!function_exists('session')) {
    function session($key = null, $value = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($key === null) {
            return $_SESSION;
        }
        
        if ($value === null) {
            return $_SESSION[$key] ?? null;
        }
        
        $_SESSION[$key] = $value;
        return $value;
    }
}

if (!function_exists('csrf')) {
    function csrf() {
        if (!session('csrf_token')) {
            session('csrf_token', bin2hex(random_bytes(32)));
        }
        return session('csrf_token');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field() {
        return '<input type="hidden" name="csrf_token" value="' . csrf() . '">';
    }
}

if (!function_exists('method_field')) {
    function method_field($method) {
        return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
    }
}

if (!function_exists('old')) {
    function old($key, $default = '') {
        return $_POST[$key] ?? $default;
    }
}

if (!function_exists('base_url')) {
    function base_url($path = '') {
        static $base = null;
        
        if ($base === null) {
            $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $script = dirname($_SERVER['SCRIPT_NAME']);
            $base = rtrim($protocol . $host . $script, '/');
        }
        
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        return base_url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('dd')) {
    function dd(...$vars) {
        echo '<pre>';
        foreach ($vars as $var) {
            print_r($var);
            echo "\n";
        }
        echo '</pre>';
        die();
    }
}

if (!function_exists('env')) {
    function env($key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}
