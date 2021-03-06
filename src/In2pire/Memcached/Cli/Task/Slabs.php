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

/**
 * Get slabs statistics.
 */
final class Slabs extends \In2pire\Cli\Task\CliTask
{
    use MemcachedTask;

    /**
     * @inheritdoc
     */
    protected $id = 'slabs';

    /**
     * Refine data.
     *
     * @param array $slabs
     *   Slabs.
     *
     * @return array
     *   Refined data.
     */
    protected function refineData($slabs)
    {
        $return = [];
        $pow1024 = 1024 * 1024;

        foreach ($slabs as $slabId => $slabStats) {
            if (empty($slabStats['total_pages'])) {
                continue;
            }

            $size = $slabStats['chunk_size'] < 1024 ? $slabStats['chunk_size'] : sprintf('%.1fK', $slabStats['chunk_size'] / 1024.0);
            $full = $slabStats['free_chunks_end'] == 0 ? 'yes' : 'no';
            $chunksSize = $slabStats['number'] * $slabStats['chunk_size'];
            $chunksSizeHuman = $chunksSize < $pow1024 ? sprintf('%.1fK', $chunksSize / 1024.0) : sprintf('%.1fM', $chunksSize / $pow1024);
            $pagesSize = $slabStats['total_pages'] * 2097152; # hardcoded item_size_max
            $pagesSizeHuman = $pagesSize < $pow1024 ? sprintf('%.1fK', $pagesSize / 1024.0) : sprintf('%.1fM', $pagesSize / $pow1024);
            $waste = 1 - $chunksSize / $pagesSize;

            $return[$slabId] = [
                'id' => $slabId,
                'item_size' => $size,
                'max_age' => $slabStats['age'],
                'total_pages' => $slabStats['total_pages'],
                'number' => $slabStats['number'],
                'full' => $full,
                'evicted' => $slabStats['evicted'],
                'evicted_time' => $slabStats['evicted_time'],
                'outofmemory' => $slabStats['outofmemory'],
                'chunk_size' => $chunksSizeHuman,
                'pages_size' => $pagesSizeHuman,
                'waste' => $waste
            ];

        }

        return $return;
    }

    /**
     * Render slabs.
     *
     * @param Symfony\Component\Console\Output\OutputInterface $output
     *   Output stream.
     * @param array $slabs
     *   Slabs.
     * @param string $format
     *   Format. Possible values: json, table (default).
     */
    protected function render(OutputInterface $output, $slabs, $format = 'table')
    {
        if ('json' == $format) {
            $output->writeln(json_encode($slabs));
            return;
        }

        $headers = [
            sprintf('%3s', '#'),
            sprintf('%10s', 'Item Size'),
            sprintf('%10s', 'Max Age'),
            sprintf('%7s', 'Pages'),
            sprintf('%7s', 'Count'),
            sprintf('%6s', 'Full?'),
            sprintf('%8s', 'Evicted'),
            sprintf('%13s', 'Evicted Time'),
            sprintf('%4s', 'OOM'),
            sprintf('%12s', 'Chunks Size'),
            sprintf('%11s', 'Pages Size'),
            sprintf('%7s','Wasted'),
        ];

        $rows = [];

        foreach ($slabs as $slabStats) {
            $rows[] = [
                sprintf('%3d', $slabStats['id']),
                sprintf('%10s', $slabStats['item_size']),
                sprintf('%9ds', $slabStats['max_age']),
                sprintf('%7d', $slabStats['total_pages']),
                sprintf('%7d', $slabStats['number']),
                sprintf('%6s', $slabStats['full']),
                sprintf('%8d', $slabStats['evicted']),
                sprintf('%13d', $slabStats['evicted_time']),
                sprintf('%4d', $slabStats['outofmemory']),
                sprintf('%12s', $slabStats['chunk_size']),
                sprintf('%11s', $slabStats['pages_size']),
                sprintf('%7.2f', $slabStats['waste']),
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
        $slabs = $this->getConnection()->getSlabsStats();

        if (empty($slabs)) {
            return static::RETURN_ERROR;
        }

        $slabs = $this->refineData($slabs);

        $this->render($output, $slabs, $this->getFormat());
    }
}
