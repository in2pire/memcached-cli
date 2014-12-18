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
     * @param Symfony\Component\Console\Output\OutputInterface $output
     *   Output stream.
     * @param array $stats
     *   Server statistics.
     * @param string $format
     *   Format. Possible values: json, table (default).
     */
    protected function render(OutputInterface $output, $stats, $format = 'table')
    {
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

        $this->render($output, $stats, $this->getFormat());
    }
}
