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
use Symfony\Component\Console\Helper\TableHelper;
use In2pire\Memcached\Client as MemcachedClient;

/**
 * Get server statistics.
 */
final class Stats extends \In2pire\Cli\Task\CliTask
{
    use MemcachedTask;

    /**
     * @inheritdoc
     */
    protected $id = 'stats';

    /**
     * Render data.
     *
     * @param array $stats
     *   Server statistics.
     * @param Symfony\Component\Console\Output\OutputInterface $output
     *   Output stream
     */
    protected function render($stats, OutputInterface $output)
    {
        $format = $this->getFormat();

        if ('json' == $format) {
            $output->writeln(json_encode($stats));
            return;
        }

        $rows = [];

        foreach ($stats as $key => $value) {
            $rows[] = [$key, $value];
        }

        $table = $this->command->getHelper('table');
        $table
            ->setLayout(TableHelper::LAYOUT_COMPACT)
            ->setRows($rows)
            ->render($output);
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $stats = $this->getConnection()->getStats();

        if (empty($stats)) {
            return static::RETURN_ERROR;
        }

        $this->render($stats, $output);
    }
}
