<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage MemcachedCli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Memcached\Cli;

class Application extends \In2pire\Cli\CliApplication
{
    /**
     * @inheritdoc
     */
    public function boot()
    {
        if ($this->booted) {
            // Already booted.
            return $this;
        }

        // Boot parent.
        parent::boot();

        // Check memcached extension.
        if (!extension_loaded('memcached')) {
            $this->response->writeln('<error>Could not found memcached extension<error>');
            return false;
        }

        return $this;
    }
}
