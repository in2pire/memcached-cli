<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage MemcachedCli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Memcached\Cli\Task;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Core Memcached Key Task.
 */
trait MemcachedKeyTask
{
    /**
     * Get hash function.
     *
     * @return string
     *   Hash function.
     */
    public function getHashFunction()
    {
        return $this->data['hash'];
    }
}
