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
 * Get slabs statistics command.
 */
class Slabs extends \In2pire\Cli\Command\CliCommand
{
    /**
     * @inheritdoc
     */
    protected $name = 'slabs';
}
