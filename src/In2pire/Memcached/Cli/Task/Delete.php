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
use In2pire\Memcached\Client as MemcachedCli;

/**
 * Delete a key.
 */
final class Delete extends \In2pire\Cli\Task\CliTask
{
    use MemcachedTask;
    use MemcachedKeyTask;

    /**
     * @inheritdoc
     */
    protected $id = 'delete';

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->getConnection();
        $key = $input->getArgument('key');
        $hash = $this->getHashFunction();
        $result = $connection->delete($key, $hash);
        $resultCode = $connection->getResultCode();

        if (MemcachedCli::KEY_NOT_FOUND == $resultCode) {
            $message = 'Could not found ' . $key . (empty($hash) ? '' : (' using ' . $hash));
            $output->getErrorOutput()->writeln('<error>' . $message . '</error>');
            return static::RETURN_ERROR;
        } elseif (!$result) {
            $message = 'Could not delete ' . $key . (empty($hash) ? '' : (' using ' . $hash));
            $output->getErrorOutput()->writeln('<error>' . $message . '</error>');
            return static::RETURN_ERROR;
        }

        $output->writeln('DELETED');
    }
}
