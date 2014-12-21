<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage MemcachedCli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Memcached\Cli\Task;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Get data by key.
 */
final class GetKeys extends \In2pire\Cli\Task\CliTask
{
    use MemcachedTask;

    /**
     * @inheritdoc
     */
    protected $id = 'get-keys';

    /**
     * Render data.
     *
     * @param Symfony\Component\Console\Output\OutputInterface $output
     *   Output stream.
     * @param array $keys
     *   Server statistics.
     * @param string $format
     *   Format. Possible values: json, <empty> (default).
     */
    protected function render(OutputInterface $output, $keys, $format = null)
    {
        if ('json' == $format) {
            $output->writeln(json_encode($keys));
            return;
        }

        $output->writeln(implode("\n", $keys));
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->getConnection();
        $slab = $input->getArgument('slab');

        if (empty($slab)) {
            $keys = $connection->getAllKeys();
        } else {
            $keys = $connection->getAllKeysInSlab($slab);
        }

        if (empty($keys)) {
            $message = 'Could not found data';
            $output->writeln('<error>' . $message . '</error>');
            return static::RETURN_ERROR;
        }

        $this->render($output, $keys, $this->getFormat());
    }
}
