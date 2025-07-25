<?php

declare(strict_types=1);

namespace Bono\Cache;

use Psr\SimpleCache\CacheInterface;

use function get_class;
use function md5;
use function serialize;

/**
 * Generic caching decorator that wraps any service and caches results.
 */
class CachingDecorator
{
    private object $service;
    private CacheInterface $cache;
    private int $defaultTtl;

    public function __construct(object $service, CacheInterface $cache, int $defaultTtl = 3600)
    {
        $this->service    = $service;
        $this->cache      = $cache;
        $this->defaultTtl = $defaultTtl;
    }

    public function __call(string $method, array $args): mixed
    {
        // Build a unique cache key based on class, method & args
        $cacheKey = $this->buildCacheKey($method, $args);

        // Check cache
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        // Call the underlying service
        $result = $this->service->$method(...$args);

        // Cache result
        $this->cache->set($cacheKey, $result, $this->defaultTtl);

        return $result;
    }

    private function buildCacheKey(string $method, array $args): string
    {
        return md5(get_class($this->service) . '::' . $method . ':' . serialize($args));
    }
}
