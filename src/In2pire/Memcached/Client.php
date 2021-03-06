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
     * sha1 hash function.
     */
    const HASH_SHA1 = 'sha1';

    /**
     * Action is success
     */
    const SUCCESS = \Memcached::RES_SUCCESS;

    /**
     * Key not found.
     */
    const KEY_NOT_FOUND = \Memcached::RES_NOTFOUND;

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

    public function getSlabsStats()
    {
        $slabs = [];
        $data = $this->request('stats items');

        if (!empty($data)) {
            $rows = explode("\n", $data);

            foreach ($rows as $row) {
                $row = trim($row);

                if (preg_match('#^STAT items:(?<id>\d+):(?<property>\w+) (?<value>\d+)$#', $row, $match)) {
                    $slabs[$match['id']][$match['property']] = $match['value'];
                }
            }
        }

        $data = $this->request('stats slabs');

        if (!empty($data)) {
            $rows = explode("\n", $data);

            foreach ($rows as $row) {
                $row = trim($row);

                if (preg_match('#^STAT (?<id>\d+):(?<property>\w+) (?<value>\d+)$#', $row, $match)) {
                    $slabs[$match['id']][$match['property']] = $match['value'];
                }
            }
        }

        $defaults = [
            'number' => null,
            'age' => null,
            'evicted' => null,
            'evicted_nonzero' => null,
            'evicted_time' => null,
            'outofmemory' => null,
            'tailrepairs' => null,
            'reclaimed' => null,
            'expired_unfetched' => null,
            'evicted_unfetched' => null,
            'crawler_reclaimed' => null,
            'chunk_size' => null,
            'chunks_per_page' => null,
            'total_pages' => null,
            'total_chunks' => null,
            'used_chunks' => null,
            'free_chunks' => null,
            'free_chunks_end' => null,
            'mem_requested' => null,
            'get_hits' => null,
            'cmd_set' => null,
            'delete_hits' => null,
            'incr_hits' => null,
            'decr_hits' => null,
            'cas_hits' => null,
            'cas_badval' => null,
            'touch_hits' => null,
        ];

        // Ensure data structure.
        foreach ($slabs as $slabId => $slab) {
            $slabs[$slabId] += $defaults;
        }

        return $slabs;
    }

    /**
     * Get sizes statistics.
     *
     * @return array
     *   Stats.
     */
    public function getSizesStats()
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
     * Get server settings.
     *
     * @return array
     *   Settings.
     */
    public function getSettings()
    {
        $settings = [];
        $data = $this->request('stats settings');

        if (empty($data)) {
            return false;
        }

        $rows = explode("\n", $data);

        foreach ($rows as $row) {
            $row = trim($row);

            if (0 === strpos($row, 'STAT ')) {
                list($key, $value) = explode(' ', substr($row, 5), 2);
                $settings[$key] = trim($value);
            }
        }

        return $settings;
    }

    /**
     * Get first slab in server that has items.
     * @return [type] [description]
     */
    public function getFirstSlab()
    {
        $data = $this->request('stats items');

        if (!empty($data) && preg_match('#STAT items:(?<id>\d+):number \d+#', $data, $match)) {
            return $match['id'];
        }

        return false;
    }

    public function getFirstItemValueInSlab($slab)
    {
        $data = $this->request('stats cachedump ' . $slab . ' 1');
        $key = null;

        if (!empty($data) && preg_match('#ITEM (?<key>[^\s]+)#', $data, $match)) {
            $key = $match['key'];
        }

        if (empty($key)) {
            return false;
        }

        return $this->get($key);
    }

    /**
     * Get first item value in server.
     *
     * @return mixed
     *   Data.
     */
    public function getFirstItemValue()
    {
        $slab = $this->getFirstSlab();

        if (empty($slab)) {
            return false;
        }

        return $this->getFirstItemValueInSlab($slab);
    }

    /**
     * Get keys in a slab.
     *
     * @param int $slab
     *   Slab ID.
     * @param int $limit
     *   (optional) Limit keys.
     *
     * @return array|boolean
     *   List of keys or false.
     */
    public function getAllKeysInSlab($slab, $limit = 0)
    {
        $data = $this->request('stats cachedump ' . $slab . ' ' . $limit);
        $key = null;

        if (empty($data)) {
            return false;
        }

        preg_match_all('#ITEM (?<key>[^\s]+)#', $data, $match);

        if (empty($match['key'])) {
            return false;
        }

        return $match['key'];
    }

    /**
     * Get keys in server.
     *
     * @return array|boolean
     *   List of keys or false.
     */
    public function getAllKeys()
    {
        $data = $this->request('stats items');

        if (empty($data)) {
            return false;
        }

        preg_match_all('#STAT items:(?<id>\d+):number (?<number>\d+)#', $data, $match);

        if (empty($match['id'])) {
            return false;
        }

        $keys = [];

        foreach ($match['id'] as $index => $slab) {
            $numKeys = (int) $match['number'][$index];

            if ($numKeys < 1) {
                continue;
            }

            $slabKeys = $this->getAllKeysInSlab($slab);

            if (is_array($slabKeys)) {
                $keys = array_merge($keys, $slabKeys);
            }
        }

        return $keys;
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

        return $this->connection->get($key);
    }

    /**
     * Check whether a key exists.
     *
     * @param string $key
     *   Key.
     * @param string $hash
     *   Hash function to hash key before get from server.
     *
     * @return boolean
     *   True or False.
     */
    public function hasKey($key, $hash = null)
    {
        $this->get($key, $hash);
        return $this->getResultCode() != static::KEY_NOT_FOUND;
    }

    /**
     * Delete by key.
     *
     * @param string $key
     *   Key.
     * @param string $hash
     *   Hash function to hash key before delete from server.
     *
     * @return mixed|boolean
     *   Data returned from server or False.
     */
    public function delete($key, $hash = null)
    {
        switch ($hash) {
            case static::HASH_MD5:
                $key = md5($key);
                break;

            case static::HASH_SHA1:
                $key = sha1($key);
                break;
        }

        return $this->connection->delete($key);
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
