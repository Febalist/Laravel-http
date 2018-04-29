<?php

namespace Febalist\Laravel\Http;

use bandwidthThrottle\tokenBucket\BlockingConsumer;
use bandwidthThrottle\tokenBucket\Rate;
use bandwidthThrottle\tokenBucket\storage\PredisStorage;
use bandwidthThrottle\tokenBucket\storage\StorageException;
use bandwidthThrottle\tokenBucket\TokenBucket;

class Throttler
{
    protected $bucket;
    protected $consumer;

    public function __construct($id, $limit = 1, $timeout = null)
    {
        $this->bucket = new TokenBucket(1,
            new Rate($limit, Rate::SECOND),
            $this->storage($id, ceil($limit / 60))
        );
        $this->consumer = new BlockingConsumer($this->bucket);
    }

    public function throttle()
    {
        $this->consume(true);
    }

    protected function storage($id, $minutes)
    {
        $name = 'throttle.'.str_slug($id, '_');

        $store = config('cache.default');
        $driver = config("cache.stores.$store.driver");
        $client = config('database.redis.client');

        if ($driver == 'redis' && $client == 'predis') {
            $connection = config('cache.stores.redis.connection');
            $parameters = config("database.redis.$connection");
            $prefix = config('cache.prefix');

            return new PredisStorage(
                "$prefix.$name",
                new \Predis\Client($parameters)
            );
        }

        return new CacheStorage($name, $minutes);
    }

    protected function consume($first)
    {
        try {
            $this->consumer->consume(1);
        } catch (StorageException $exception) {
            if ($first && str_contains($exception->getMessage(), 'The string is not 64 bit long')) {
                $this->bucket->bootstrap(1);
                $this->consume(false);
            } else {
                throw($exception);
            }
        }
    }
}
