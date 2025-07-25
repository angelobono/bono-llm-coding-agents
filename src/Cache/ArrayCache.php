<?php

declare(strict_types=1);

namespace Bono\Cache;

use DateInterval;
use DateTime;
use Psr\SimpleCache\CacheInterface;
use Traversable;

use function array_key_exists;
use function iterator_to_array;
use function time;

class ArrayCache implements CacheInterface
{
    private array $cache   = [];
    private array $expires = [];

    private function ttlToSeconds(DateInterval|int|null $ttl): int
    {
        if ($ttl instanceof DateInterval) {
            return (new DateTime())->add($ttl)->getTimestamp() - time();
        }
        return $ttl ?? 0;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, $this->cache)) {
            return $default;
        }
        if (isset($this->expires[$key]) && $this->expires[$key] < time()) {
            unset($this->cache[$key], $this->expires[$key]);
            return $default;
        }
        return $this->cache[$key];
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->cache[$key] = $value;
        $ttlSec            = $this->ttlToSeconds($ttl);
        if ($ttlSec > 0) {
            $this->expires[$key] = time() + $ttlSec;
        } else {
            unset($this->expires[$key]);
        }
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->cache[$key], $this->expires[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->cache   = [];
        $this->expires = [];
        return true;
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
        return array_key_exists($key, $this->cache)
            && (! isset($this->expires[$key]) || $this->expires[$key] >= time());
    }
}
