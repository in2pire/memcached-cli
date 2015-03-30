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
 * Validator for memcached connection pool.
 */
class MemcachedConnectionPool extends \In2pire\Cli\Validator\CliValidator
{
    /**
     * @inheritdoc
     */
    public function validate(InputInterface $input)
    {
        $pool = $input->getOption('pool');
        $pool = explode(',', $pool ? $pool : '');
        $pool = array_filter($pool);

        if (empty($pool)) {
            throw new \UnexpectedValueException('Pool cannot be empty');
        }

        $connections = [];

        foreach ($pool as $server) {
            @list($server, $port) = explode(':', $server);

            if (empty($port)) {
                $port = '11211';
            }

            $key = $server . ':' . $port;

            if (empty($connections[$key])) {
                $connection = MemcachedClient::getConnection($server, $port);

                if (!$connection->isConnected()) {
                    throw new \RuntimeException('Could not connect to server');
                }

                $connections[$key] = $connection;
            }
        }

        return [
            'connections' => $connections,
        ];
    }
}
