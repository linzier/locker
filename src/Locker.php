<?php

namespace Dev\Locker;

/**
 * Lock implementation based on Redis.
 * The current implementation does not involve distributed Redis.
 * Class Locker
 */
class Locker
{
    const STATUS_LOCK = 1;
    const STATUS_UNLOCK = 0;

    private $redis;
    // Public identifier of the lock
    private $key;
    private $keyPrefix = 'redis-lock-';
    // Lock timeout duration
    private $timeout;
    // Lock status
    private $status;
    // Internal identifier of the lock object, unique per instance
    private $privateKey;

    public function __construct(\Redis $redis, $key, $timeout = 5)
    {
        $this->redis = $redis;
        $this->key = $this->keyPrefix . $key;
        $this->timeout = $timeout;

        // Initialize lock status
        $this->status = self::STATUS_UNLOCK;

        // Internal identifier
        $this->privateKey = $this->privateKey();
    }

    /**
     * Force unlock before object destruction.
     */
    public function __destruct()
    {
        $this->unlock();
    }

    /**
     * Acquire the lock.
     * @return bool
     */
    public function lock()
    {
        if ($this->status === self::STATUS_LOCK) {
            return true;
        }

        if ($this->redis->set($this->key, $this->privateKey, ['nx', 'ex' => $this->timeout])) {
            $this->status = self::STATUS_LOCK;
            return true;
        }

        return false;
    }

    /**
     * Release the lock.
     * Can only release the lock acquired by this instance.
     */
    public function unlock()
    {
        if ($this->status !== self::STATUS_LOCK) {
            return;
        }

        // Use Lua script to ensure atomicity of get and del operations
        $lua = <<<SCR
        if (redis.call('get', KEYS[1]) == ARGV[1])
        then
            redis.call('del', KEYS[1]);
        end
        return 1;
        SCR;

        if ($this->redis->eval($lua, [$this->key, $this->privateKey], 1) == 1) {
            $this->status = self::STATUS_UNLOCK;
        } else {
            // Unlock failed; no extra handling for now, let it expire automatically
        }
    }

    private function privateKey()
    {
        return uniqid(posix_getpid());
    }
}
