<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage MemcachedCli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Memcached\Cli\Task;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Core Memcached Task.
 */
trait MemcachedTask
{
    /**
     * Get connection to memcache server.
     *
     * @return In2pire\Memcached\Client
     *   The connection to server.
     */
    public function getConnection()
    {
        return empty($this->data['connection']) ? null : $this->data['connection'];
    }

    /**
     * Get host.
     *
     * @return string
     *   Hostname.
     */
    public function getHost()
    {
        return empty($this->data['host']) ? 'localhost' : $this->data['host'];
    }

    /**
     * Get port.
     *
     * @return string
     *   Port.
     */
    public function getPort()
    {
        return empty($this->data['port']) ? '11211' : $this->data['port'];
    }

    /**
     * Get display format
     *
     * @return string
     *   Format.
     */
    public function getFormat()
    {
        return empty($this->data['format']) ? 'json' : $this->data['format'];
    }

    /**
     * Render item value.
     *
     * @param Symfony\Component\Console\Output\OutputInterface $output
     *   Output stream.
     * @param mixed $data
     *   Data.
     * @param string $format
     *   Format. Possible values: json (default), export, dump, serialize.
     */
    public function renderItemValue(OutputInterface $output, $data, $format)
    {
        switch ($format) {
            default:
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

            case 'serialize':
                $data = serialize($data);
                break;
        }

        $output->writeln($data);
    }
}
