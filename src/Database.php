<?php
namespace Mas\Ali;

class Database {
    private static $connections = [];
    private static $config = [];
    
    public static function setConfig($config) {
        self::$config = $config;
    }
    
    public static function connection($name = 'default') {
        if (!isset(self::$connections[$name])) {
            if (!isset(self::$config[$name])) {
                throw new \Exception("Database connection '$name' not found in config");
            }
            self::$connections[$name] = self::connect(self::$config[$name]);
        }
        return self::$connections[$name];
    }
    
    private static function connect($config) {
        $driver = $config['driver'] ?? 'mysql';
        
        try {
            switch($driver) {
                case 'mysql':
                case 'pgsql':
                case 'sqlsrv':
                    return self::connectPdo($config);
                case 'sqlite':
                    return new \PDO("sqlite:{$config['database']}");
                case 'redis':
                    return self::connectRedis($config);
                default:
                    throw new \Exception("Driver '$driver' tidak didukung");
            }
        } catch (\Exception $e) {
            throw new \Exception("Gagal konek ke $driver: " . $e->getMessage());
        }
    }
    
    private static function connectPdo($config) {
        $dsn = $config['driver'] . ":host={$config['host']};dbname={$config['database']}";
        $conn = new \PDO($dsn, $config['username'] ?? null, $config['password'] ?? null);
        $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
        return $conn;
    }
    
    private static function connectRedis($config) {
        if (!class_exists('Redis')) {
            throw new \Exception("Redis extension tidak terinstall");
        }
        $redis = new \Redis();
        $redis->connect($config['host'], $config['port'] ?? 6379);
        if (!empty($config['password'])) {
            $redis->auth($config['password']);
        }
        return $redis;
    }
}
