<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage MemcachedCli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Memcached;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;

/**
 * Memcached Client.
 */
class Client
{
    /**
     * md5 hash function.
     */
    const HASH_MD5 = 'md5';

    /**
     * sha1 hash function
     */
    const HASH_SHA1 = 'sha1';

    /**
     * List of opened connections.
     *
     * @var array
     */
    protected static $connections = [];

    /**
     * Connection to memcached server.
     *
     * @var resource
     */
    protected $connection = null;

    /**
     * Server hostname.
     *
     * @var string
     */
    protected $host = null;

    /**
     * Server port.
     *
     * @var string
     */
    protected $port = null;

    /**
     * Constructor
     *
     * @param string $host
     *   Server hostname.
     * @param string $port
     *   Server port.
     */
    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
        $this->connection = null;

        $this->connect();
    }

    /**
     * Client to string
     *
     * @return string
     *   Client ID.
     *
     * @see In2pire\Memcached\Client::getConnection()
     */
    public function __toString()
    {
        return static::getUniqueId($this->host, $this->port);
    }

    /**
     * Magic methods to support directly call to memcached.
     *
     * @param string $name
     *   Function name.
     * @param array $arguments
     *   Arguments
     *
     * @return mixed
     *   Depends on memcached functions.
     */
    public function __call($name, $arguments)
    {
        if ($this->isConnected() && is_callable(array($this->connection, $name))) {
            $result = call_user_func_array(array($this->connection, $name), $arguments);
            return $result;
        }

        throw new \RuntimeException('Call to undefined method ' . __CLASS__ . '::' . $name . '()');
    }

    /**
     * Get server host.
     *
     * @return string
     *   Server host.
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get server port.
     *
     * @return string
     *   Port.
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Get server statistics.
     *
     * @return array|boolean
     *   Array of statistics or False.
     */
    public function getStats()
    {
        $stats = [];
        $data = $this->request('stats');

        if (empty($data)) {
            return false;
        }

        $rows = explode("\n", $data);

        foreach ($rows as $row) {
            $row = trim($row);

            if (0 === strpos($row, 'STAT ')) {
                list($key, $value) = explode(' ', substr($row, 5), 2);
                $stats[$key] = trim($value);
            }
        }

        return $stats;
    }

    public function getSlabs()
    {
        $stats = [];
        $data = $this->request('stats items');

        if (!empty($data)) {
            $rows = explode("\n", $data);

            foreach ($rows as $row) {
                $row = trim($row);

                if (preg_match('#^STAT items:(?<id>\d+):(?<property>\w+) (?<value>\d+)$#', $row, $match)) {
                    $stats[$match['id']][$match['property']] = $match['value'];
                }
            }
        }

        $data = $this->request('stats slabs');

        if (!empty($data)) {
            $rows = explode("\n", $data);

            foreach ($rows as $row) {
                $row = trim($row);

                if (preg_match('#^STAT (?<id>\d+):(?<property>\w+) (?<value>\d+)$#', $row, $match)) {
                    $stats[$match['id']][$match['property']] = $match['value'];
                }
            }
        }

        return $stats;
    }

    /**
     * Get sizes statistics.
     *
     * @return array
     *   Stats.
     */
    public function getSizes()
    {
        $stats = [];
        $data = $this->request('stats sizes');

        if (empty($data)) {
            return false;
        }

        $rows = explode("\n", $data);

        foreach ($rows as $row) {
            $row = trim($row);

            if (preg_match('#^STAT\s+(?<key>\S*)\s+(?<value>.*)$#', $row, $match)) {
                $stats[$match['key']] = $match['value'];
            }
        }

        return $stats;
    }

    /**
     * Get data by key.
     *
     * @param string $key
     *   Key.
     * @param string $hash
     *   Hash function to hash key before get from server.
     *
     * @return mixed|boolean
     *   Data returned from server or False.
     */
    public function get($key, $hash = null)
    {
        switch ($hash) {
            case static::HASH_MD5:
                $key = md5($key);
                break;

            case static::HASH_SHA1:
                $key = sha1($key);
                break;
        }

        $data = $this->connection->get($key);

        return $data;
    }

    /**
     * Send a command request to server.
     *
     * @param string $command
     *   Command.
     *
     * @return string|boolean
     *   Response from server or False.
     */
    public function request($command)
    {
        $cmd = 'echo ' . ProcessUtils::escapeArgument($command) . ' | nc ' . ProcessUtils::escapeArgument($this->host) . ' ' . ProcessUtils::escapeArgument($this->port);

        $process = new Process($cmd, getcwd());
        $process->run();

        if (!$process->isSuccessful()) {
            return false;
        }

        return $process->getOutput();
    }

    /**
     * Connect to server. This one is just a fake method because addServer()
     * does not connect to server.
     *
     * @return boolean
     *   True or False.
     */
    public function connect()
    {
        $this->connection = new \Memcached();
        $result = $this->connection->addServer($this->host, $this->port);

        if (!$result) {
            unset($this->connection);
        }

        return $result;
    }

    /**
     * Is server connected.
     *
     * @return boolean
     *   True or False.
     */
    public function isConnected()
    {
        return !empty($this->connection);
    }

    /**
     * Close connection to server.
     */
    public function close()
    {
        $this->connection->quit();
        unset($this->connection);
        $this->connection = null;
    }

    /**
     * Get unique id.
     *
     * @param string $host
     *   Server hostname.
     * @param string $port
     *   Server port.
     *
     * @return string
     *   Unique ID.
     */
    protected static function getUniqueId($host, $port)
    {
        return $host . ':' . $port;
    }

    /**
     * Get connection.
     *
     * @param string $host
     *   Server hostname.
     * @param string $port
     *   Server port.
     *
     * @return In2pire\Memcached\Client
     *   Client connection to memcached server.
     */
    public static function getConnection($host, $port)
    {
        $uniqId = static::getUniqueId($host, $port);

        if (isset(static::$connections[$uniqId])) {
            return static::$connections[$uniqId];
        }

        static::$connections[$uniqId] = $connection = new static($host, $port);

        return $connection;
    }
}
