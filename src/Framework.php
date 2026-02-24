<?php
namespace Mas\Ali;

class Framework {
    private static $instance = null;
    private $config = [];
    private $router = null;
    
    private function __construct() {
        $this->router = new Router();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function loadConfig($path) {
        if (file_exists($path)) {
            $this->config = require $path;
            $this->boot();
        }
        return $this;
    }
    
    private function boot() {
        // Set database config
        if (isset($this->config['database'])) {
            Database::setConfig($this->config['database']);
        }
        
        // Set router base path
        if (isset($this->config['app']['base_path'])) {
            Router::setBasePath($this->config['app']['base_path']);
        }
        
        // Set timezone
        if (isset($this->config['app']['timezone'])) {
            date_default_timezone_set($this->config['app']['timezone']);
        }
    }
    
    public function loadRoutes($path) {
        if (file_exists($path)) {
            require $path;
        }
        return $this;
    }
    
    public function run() {
        // Start session
        $this->startSession();
        
        // Run router
        $this->router->run();
    }
    
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function config($key = null, $default = null) {
        if ($key === null) {
            return $this->config;
        }
        
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
}
