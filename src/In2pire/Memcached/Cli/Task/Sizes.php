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
 * Get slabs statistics.
 */
final class Sizes extends \In2pire\Cli\Task\CliTask
{
    use MemcachedTask;

    /**
     * @inheritdoc
     */
    protected $id = 'sizes';

    /**
     * Render data.
     *
     * @param array $stats
     *   Sizes statistics.
     * @param Symfony\Component\Console\Output\OutputInterface $output
     *   Output stream.
     */
    protected function render($stats, OutputInterface $output)
    {
        $format = $this->getFormat();
        ksort($stats);

        if ('json' == $format) {
            $output->writeln(json_encode($stats));
            return;
        }

        $headers = [
            sprintf('%10s', 'Size'),
            sprintf('%6s', 'Count'),
        ];

        $rows = [];

        foreach ($stats as $size => $count) {
            $rows[] = [
                sprintf('%10d', $size),
                sprintf('%6d', $count),
            ];
        }

        $table = $this->command->getHelper('table');
        $table
            ->setLayout(TableHelper::LAYOUT_COMPACT)
            ->setHeaders($headers)
            ->setRows($rows)
            ->render($output);
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $stats = $this->getConnection()->getSizesStats();

        if (empty($stats)) {
            return static::RETURN_ERROR;
        }

        $this->render($stats, $output);
    }
}
