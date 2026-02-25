<?php

namespace Mas\Ali;

class Hardisk
{
    private static array $disks = [];
    private $diskName;
    private $config;

    public static function setDisk(string $name, array $config)
    {
        self::$disks[$name] = $config;
    }

    public static function disk(string $name): self
    {
        if (!isset(self::$disks[$name])) {
            throw new \Exception("Disk '$name' belum dikonfigurasi");
        }

        $instance = new self();
        $instance->diskName = $name;
        $instance->config = self::$disks[$name];
        return $instance;
    }

    public function put(string $filename, string $content): bool
    {
        if ($this->config['driver'] === 'local') {
            $path = rtrim($this->config['root'], '/') . '/' . $filename;
            $dir = dirname($path);
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            return file_put_contents($path, $content) !== false;
        }

        if ($this->config['driver'] === 'ftp') {
            $conn = ftp_connect($this->config['host'], $this->config['port'] ?? 21);
            if (!$conn) throw new \Exception("Gagal connect FTP");
            if (!ftp_login($conn, $this->config['username'], $this->config['password'])) {
                throw new \Exception("FTP login gagal");
            }
            $remotePath = $filename;
            $tmpFile = tempnam(sys_get_temp_dir(), 'ftp_');
            file_put_contents($tmpFile, $content);
            $result = ftp_put($conn, $remotePath, $tmpFile, FTP_BINARY);
            unlink($tmpFile);
            ftp_close($conn);
            return $result;
        }

        throw new \Exception("Driver '{$this->config['driver']}' tidak didukung");
    }

    public function get(string $filename): string
    {
        if ($this->config['driver'] === 'local') {
            $path = rtrim($this->config['root'], '/') . '/' . $filename;
            if (!file_exists($path)) throw new \Exception("File '$filename' tidak ditemukan");
            return file_get_contents($path);
        }

        if ($this->config['driver'] === 'ftp') {
            $conn = ftp_connect($this->config['host'], $this->config['port'] ?? 21);
            if (!$conn) throw new \Exception("Gagal connect FTP");
            if (!ftp_login($conn, $this->config['username'], $this->config['password'])) {
                throw new \Exception("FTP login gagal");
            }
            $tmpFile = tempnam(sys_get_temp_dir(), 'ftp_');
            if (!ftp_get($conn, $tmpFile, $filename, FTP_BINARY)) {
                ftp_close($conn);
                unlink($tmpFile);
                throw new \Exception("File '$filename' tidak ditemukan di FTP");
            }
            $content = file_get_contents($tmpFile);
            unlink($tmpFile);
            ftp_close($conn);
            return $content;
        }

        throw new \Exception("Driver '{$this->config['driver']}' tidak didukung");
    }

    public function delete(string $filename): bool
    {
        if ($this->config['driver'] === 'local') {
            $path = rtrim($this->config['root'], '/') . '/' . $filename;
            return file_exists($path) ? unlink($path) : false;
        }

        if ($this->config['driver'] === 'ftp') {
            $conn = ftp_connect($this->config['host'], $this->config['port'] ?? 21);
            if (!$conn) throw new \Exception("Gagal connect FTP");
            if (!ftp_login($conn, $this->config['username'], $this->config['password'])) {
                throw new \Exception("FTP login gagal");
            }
            $result = ftp_delete($conn, $filename);
            ftp_close($conn);
            return $result;
        }

        throw new \Exception("Driver '{$this->config['driver']}' tidak didukung");
    }

    public function exists(string $filename): bool
    {
        if ($this->config['driver'] === 'local') {
            return file_exists(rtrim($this->config['root'], '/') . '/' . $filename);
        }

        if ($this->config['driver'] === 'ftp') {
            $conn = ftp_connect($this->config['host'], $this->config['port'] ?? 21);
            if (!$conn) throw new \Exception("Gagal connect FTP");
            if (!ftp_login($conn, $this->config['username'], $this->config['password'])) {
                throw new \Exception("FTP login gagal");
            }
            $list = ftp_nlist($conn, dirname($filename));
            ftp_close($conn);
            return $list && in_array($filename, $list);
        }

        throw new \Exception("Driver '{$this->config['driver']}' tidak didukung");
    }
}
