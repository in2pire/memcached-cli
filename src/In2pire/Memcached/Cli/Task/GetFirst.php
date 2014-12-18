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
final class GetFirst extends \In2pire\Cli\Task\CliTask
{
    use MemcachedTask;

    /**
     * @inheritdoc
     */
    protected $id = 'get-first';

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->getConnection();
        $slab = $input->getArgument('slab');

        if (empty($slab)) {
            $data = $connection->getFirstItemValue();
        } else {
            $data = $connection->getFirstItemValueInSlab($slab);
        }

        if (empty($data)) {
            $message = 'Could not found data';
            $output->writeln('<error>' . $message . '</error>');
            return static::RETURN_ERROR;
        }

        $this->renderItemValue($output, $data, $this->getFormat());
    }
}
