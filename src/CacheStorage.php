<?php

namespace Febalist\LaravelHttp;

use bandwidthThrottle\tokenBucket\storage\scope\GlobalScope;
use bandwidthThrottle\tokenBucket\storage\Storage;
use Cache;
use malkusch\lock\mutex\NoMutex;

final class CacheStorage implements Storage, GlobalScope
{
    protected $mutex;
    protected $id;
    protected $minutes;

    public function __construct($id, $minutes)
    {
        $this->mutex = new NoMutex();
        $this->id = $id;
        $this->minutes = $minutes;
    }

    public function isBootstrapped()
    {
        return !is_null($this->getMicrotime());
    }

    public function bootstrap($microtime)
    {
        $this->setMicrotime($microtime);
    }

    public function remove()
    {
        Cache::forget($this->getKey());
    }

    public function setMicrotime($microtime)
    {
        Cache::put($this->getKey(), $microtime, $this->minutes);
    }

    public function getMicrotime()
    {
        return Cache::get($this->getKey());
    }

    public function getMutex()
    {
        return $this->mutex;
    }

    public function letMicrotimeUnchanged()
    {
    }

    protected function getKey()
    {
        return "throttle.$this->id";
    }
}
