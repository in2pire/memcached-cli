<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage MemcachedCli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Memcached\Cli\Validator;

use Symfony\Component\Console\Input\InputInterface;
use In2pire\Memcached\Client as MemcachedClient;

/**
 * Validator for memcached configuration (host and port).
 */
class MemcachedConnection extends \In2pire\Cli\Validator\CliValidator
{
    /**
     * @inheritdoc
     */
    public function validate(InputInterface $input)
    {
        $host = $input->getOption('host');
        $port = $input->getOption('port');

        if (empty($host)) {
            throw new \UnexpectedValueException('Host cannot be empty');
        }

        if (empty($port)) {
            throw new \UnexpectedValueException('Port cannot be empty');
        }

        $connection = MemcachedClient::getConnection($host, $port);

        if (!$connection->isConnected()) {
            throw new \RuntimeException('Could not connect to server');
        }

        return [
            'connection' => $connection,
            'host' => $host,
            'port' => $port
        ];
    }
}
