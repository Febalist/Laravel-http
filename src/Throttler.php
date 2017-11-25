<?php

namespace Febalist\LaravelHttp;

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

    protected function storage($id, $limit)
    {
        $id = str_slug($id, '_');
        if (config('cache.default') == 'redis') {
            $prefix = config('cache.prefix');

            return new PredisStorage(
                "$prefix.throttle.$id",
                new \Predis\Client()
            );
        }

        return new CacheStorage($id, $limit);
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
