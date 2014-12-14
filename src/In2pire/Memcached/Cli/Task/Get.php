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
 * Get data by key.
 */
final class Get extends \In2pire\Cli\Task\CliTask
{
    use MemcachedTask;

    /**
     * @inheritdoc
     */
    protected $id = 'get';

    /**
     * Get display format
     *
     * @return string
     *   Format.
     */
    public function getFormat()
    {
        return $this->data['format'];
    }

    /**
     * Get hash function
     *
     * @return string
     *   Hash function.
     */
    public function getHashFunction()
    {
        return $this->data['hash'];
    }

    /**
     * Render data.
     *
     * @param array $stats
     *   Server statistics.
     * @param Symfony\Component\Console\Output\OutputInterface $output
     *   Output stream
     */
    protected function render($data, OutputInterface $output)
    {
        $format = $this->getFormat();

        switch ($format) {
            case 'json':
                $data = json_encode($data);
                break;

            case 'export':
                $data = var_export($data, true);
                break;

            case 'dump':
                ob_start();
                var_dump($data);
                $data = ob_get_clean();
                break;

            default:
            case 'serialize':
                $data = serialize($data);
                break;
        }

        $output->writeln($data);
    }

    /**
     * @inheritdoc
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');
        $hash = $this->getHashFunction();
        $data = $this->getConnection()->get($key, $hash);

        if (empty($data)) {
            throw new \RuntimeException('Could not found ' . $key . (empty($hash) ? '' : (' using ' . $hash)));
        }

        $this->render($data, $output);
    }
}
