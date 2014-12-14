<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage MemcachedCli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Memcached\Cli\Command;

/**
 * Get data by key command.
 */
class Get extends \In2pire\Cli\Command\CliCommand
{
    /**
     * @inheritdoc
     */
    protected $name = 'get';
}
