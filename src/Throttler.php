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
        $prefix = config('cache.prefix');
        $id = str_slug($id, '_');
        $client = new \Predis\Client();
        $storage = new PredisStorage("$prefix.api_throttle.$id", $client);
        $rate = new Rate($limit, Rate::SECOND);
        $this->bucket = new TokenBucket(1, $rate, $storage);
        $this->consumer = new BlockingConsumer($this->bucket);
    }

    public function throttle()
    {
        $this->consume(true);
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
