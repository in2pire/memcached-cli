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
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');
        $hash = $this->getHashFunction();
        $data = $this->getConnection()->get($key, $hash);

        if (empty($data)) {
            $message = 'Could not found ' . $key . (empty($hash) ? '' : (' using ' . $hash));
            $output->writeln('<error>' . $message . '</error>');
            return static::RETURN_ERROR;
        }

        $this->renderItemValue($output, $data, $this->getFormat());
    }
}
