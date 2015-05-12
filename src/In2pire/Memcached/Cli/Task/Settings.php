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
 * Get server settings.
 */
final class Settings extends \In2pire\Cli\Task\CliTask
{
    use MemcachedTask;

    /**
     * @inheritdoc
     */
    protected $id = 'settings';

    /**
     * Render data.
     *
     * @param Symfony\Component\Console\Output\OutputInterface $output
     *   Output stream.
     * @param array $settings
     *   Server settings.
     * @param string $format
     *   Format. Possible values: json, table (default).
     */
    protected function render(OutputInterface $output, $settings, $format = 'table')
    {
        if ('json' == $format) {
            $output->writeln(json_encode($settings));
            return;
        }

        $rows = [];

        foreach ($settings as $key => $value) {
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
        $settings = $this->getConnection()->getSettings();

        if (empty($settings)) {
            return static::RETURN_ERROR;
        }

        $this->render($output, $settings, $this->getFormat());
    }
}
