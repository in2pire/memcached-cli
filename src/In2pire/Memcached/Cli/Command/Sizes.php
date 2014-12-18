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
 * Get server statistics command.
 */
class Sizes extends \In2pire\Cli\Command\CliCommand
{
    /**
     * @inheritdoc
     */
    protected $name = 'sizes';
}
