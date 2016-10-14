<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage MemcachedCli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Memcached\Cli;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use In2pire\Cli\Application as BaseApplication;

class Application extends BaseApplication
{
    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if (true === $input->hasParameterOption(array('--bash-completion'))) {
            $output->writeln($this->getBashCompletion());
            return 0;
        }

        if (true === $input->hasParameterOption(array('--version', '-V'))) {
            $output->writeln($this->getLongVersion());
            return 0;
        }

        $name = $this->getCommandName($input);

        if (true === $input->hasParameterOption(array('--help', '-h'))) {
            $name = 'help';
        }

        if (!$name) {
            $name = 'list';
        }

        if ('help' !== $name && 'list' !== $name) {
            // Check memcached extension.
            if (!extension_loaded('memcached')) {
                $output->getErrorOutput()->writeln('<error>Could not found memcached extension</error>');
                return 1;
            }
        }

        return parent::doRun($input, $output);
    }
}
