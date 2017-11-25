<?php

namespace Febalist\LaravelHttp;

use bandwidthThrottle\tokenBucket\storage\scope\GlobalScope;
use bandwidthThrottle\tokenBucket\storage\Storage;
use Cache;
use malkusch\lock\mutex\NoMutex;

final class CacheStorage implements Storage, GlobalScope
{
    protected $mutex;
    protected $name;
    protected $minutes;

    public function __construct($name, $minutes)
    {
        $this->mutex = new NoMutex();
        $this->name = $name;
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
        Cache::forget($this->name);
    }

    public function setMicrotime($microtime)
    {
        Cache::put($this->name, $microtime, $this->minutes);
    }

    public function getMicrotime()
    {
        return Cache::get($this->name);
    }

    public function getMutex()
    {
        return $this->mutex;
    }

    public function letMicrotimeUnchanged()
    {
    }
}
