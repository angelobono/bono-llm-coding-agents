<?php

declare(strict_types=1);

namespace Bono\Cache;

use DateInterval;
use DateTime;
use Psr\SimpleCache\CacheInterface;
use Traversable;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function is_dir;
use function iterator_to_array;
use function md5;
use function mkdir;
use function rtrim;
use function serialize;
use function time;
use function unlink;
use function unserialize;

class FileCache implements CacheInterface
{
    private string $cacheDir;

    public function __construct(string $cacheDir = 'data/cache')
    {
        $this->cacheDir = rtrim($cacheDir, '/');
        if (! is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    private function getFile(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }

    private function ttlToSeconds(DateInterval|int|null $ttl): int
    {
        if ($ttl instanceof DateInterval) {
            return (new DateTime())->add($ttl)->getTimestamp() - time();
        }
        return $ttl ?? 0;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getFile($key);
        if (! file_exists($file)) {
            return $default;
        }
        $data = @unserialize(file_get_contents($file));
        if (! $data || (isset($data['expires']) && $data['expires'] < time())) {
            @unlink($file);
            return $default;
        }
        return $data['value'];
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $file   = $this->getFile($key);
        $ttlSec = $this->ttlToSeconds($ttl);
        $data   = [
            'value'   => $value,
            'expires' => $ttlSec > 0 ? time() + $ttlSec : null,
        ];
        return file_put_contents($file, serialize($data)) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->getFile($key);
        return file_exists($file) ? @unlink($file) : true;
    }

    public function clear(): bool
    {
        $ok = true;
        foreach (glob($this->cacheDir . '/*.cache') as $file) {
            $ok = $ok && @unlink($file);
        }
        return $ok;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        if ($keys instanceof Traversable) {
            $keys = iterator_to_array($keys, false);
        }
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        if ($values instanceof Traversable) {
            $values = iterator_to_array($values, true);
        }
        $ok = true;
        foreach ($values as $key => $value) {
            $ok = $ok && $this->set($key, $value, $ttl);
        }
        return $ok;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        if ($keys instanceof Traversable) {
            $keys = iterator_to_array($keys, false);
        }
        $ok = true;
        foreach ($keys as $key) {
            $ok = $ok && $this->delete($key);
        }
        return $ok;
    }

    public function has(string $key): bool
    {
        $file = $this->getFile($key);
        if (! file_exists($file)) {
            return false;
        }
        $data = @unserialize(file_get_contents($file));
        return $data && (! isset($data['expires']) || $data['expires'] >= time());
    }
}
