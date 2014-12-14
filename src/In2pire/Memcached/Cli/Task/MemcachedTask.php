<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage MemcachedCli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Memcached\Cli\Task;

/**
 * Core Memcached Task.
 */
trait MemcachedTask
{
    /**
     * Get connection to memcache server.
     *
     * @return In2pire\Memcached\Client
     *   The connection to server.
     */
    public function getConnection()
    {
        return $this->data['connection'];
    }

    /**
     * Get host.
     *
     * @return string
     *   Hostname.
     */
    public function getHost()
    {
        return $this->data['host'];
    }

    /**
     * Get port.
     *
     * @return string
     *   Port.
     */
    public function getPort()
    {
        return $this->data['port'];
    }
}
