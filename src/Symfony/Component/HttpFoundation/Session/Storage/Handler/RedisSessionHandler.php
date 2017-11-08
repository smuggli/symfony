<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\Session\Storage\Handler;

/**
 * Redis based session storage handler
 *
 * @see    https://github.com/phpredis/phpredis
 * @see    https://github.com/nrk/predis
 *
 * @author Stephan Muggli <stephan.muggli@googlemail.com>
 */
class RedisSessionHandler extends AbstractSessionHandler
{
    /**
     * @var int
     */
    const DEFAULT_TTL = 86400;

    /**
     * @var \Redis|\Predis\Client
     */
    private $redis;

    /**
     * @var int Time to live in seconds
     */
    private $ttl;

    /**
     * @var string Prefix for session keys
     */
    private $prefix;

    /**
     * List of available options:
     * * prefix: The prefix to use for the redis keys in order to avoid collision
     * * expiretime: The time to live in seconds.
     *
     * @param \Redis|\Predis\Client $redis   A client to communicate with Redis
     * @param array                 $options An associative array of Redis options
     *
     * @throws \InvalidArgumentException Redis or Predis\Client instance required
     * @throws \InvalidArgumentException When unsupported options are passed
     */
    public function __construct($redis, array $options = [])
    {
        if (!($redis instanceof \Redis || $redis instanceof \Predis\Client)) {
            throw new \InvalidArgumentException('Redis or Predis\Client instance required');
        }

        $this->redis = $redis;

        if ($diff = array_diff(array_keys($options), ['prefix', 'expiretime'])) {
            throw new \InvalidArgumentException(sprintf(
                'The following options are not supported "%s"', implode(', ', $diff)
            ));
        }

        $this->ttl = isset($options['expiretime']) ? (int)$options['expiretime'] : self::DEFAULT_TTL;
        $this->prefix = isset($options['prefix']) ? $options['prefix'] : 'sf2s';
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function doRead($sessionId)
    {
        return $this->redis->get($this->prefix . $sessionId) ?: '';
    }

    /**
     * {@inheritdoc}
     */
    public function doWrite($sessionId, $data)
    {
        return $this->redis->set($this->prefix . $sessionId, $data, $this->ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function doDestroy($sessionId)
    {
        if (!$this->redis->exists($this->prefix . $sessionId)) {
            return true;
        }

        return (bool)$this->redis->del($this->prefix . $sessionId);
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime)
    {
        // Let Redis use its eviction policy for that
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function updateTimestamp($sessionId, $data)
    {
        return $this->redis->expire($this->prefix . $sessionId, $this->ttl);
    }

}
