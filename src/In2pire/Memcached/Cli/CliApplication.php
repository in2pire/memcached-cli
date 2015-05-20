<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage MemcachedCli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Memcached\Cli;

class CliApplication extends \In2pire\Cli\CliApplication
{
    /**
     * @inheritdoc
     */
    protected function getRunner()
    {
        if (null === $this->runner) {
            $this->runner = new Application($this->name, $this->version, $this->description);
        }

        return $this->runner;
    }

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

        $description = $this->runner->getDescription();

        if (!empty($description)) {
            $description .= "\n\n";
        }

        if (class_exists('Memcached')) {
            $description .= '<comment>igbinary support</comment>  ' . (\Memcached::HAVE_IGBINARY ? 'yes' : 'no') . "\n";
            $description .= '<comment>json support</comment>      ' . (\Memcached::HAVE_JSON ? 'yes' : 'no');
        } else {
            $description .= '<error>Could not found memcached extension</error>';
        }

        $this->runner->setDescription($description);

        return $this;
    }
}
