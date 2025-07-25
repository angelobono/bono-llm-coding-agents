<?php

declare(strict_types=1);

namespace Bono\Cache;

use Psr\SimpleCache\CacheInterface;

use Psr\SimpleCache\InvalidArgumentException;

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

    /**
     * @throws InvalidArgumentException
     */
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

        if ($result === null) {
            // If the result is null, we don't cache it
            return null;
        }
        if (is_string($result)) {
            // If the result is a string, we can cache it directly
            $result = trim($result);
        } elseif (is_array($result)) {
            // If the result is an array, we can cache it as is
            $result = array_filter($result, static fn($value) => $value !== null);
        } elseif (is_object($result)) {
            // If the result is an object, we can cache it as is
            // Ensure the object is serializable
            if (!method_exists($result, '__serialize') && !method_exists($result, '__unserialize')) {
                throw new \RuntimeException('Result object must be serializable');
            }
        } else {
            throw new \RuntimeException('Unsupported result type for caching');
        }
        if (empty($result)) {
            // If the result is empty, we don't cache it
            return $result;
        }
        // Cache result
        $this->cache->set($cacheKey, $result, $this->defaultTtl);
        return $result;
    }

    private function buildCacheKey(string $method, array $args): string
    {
        return md5(get_class($this->service) . '::' . $method . ':' . serialize($args));
    }
}
