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
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Process\Process;

/**
 * Get server statistics.
 */
final class Top extends \In2pire\Cli\Task\CliTask
{
    use MemcachedTask;

    /**
     * Left alignment.
     */
    const ALIGN_LEFT = 'left';

    /**
     * Right alignment.
     */
    const ALIGN_RIGHT = 'right';

    /**
     * Center alignment.
     */
    const ALIGN_CENTER = 'center';

    /**
     * @inheritdoc
     */
    protected $id = 'top';

    /**
     * Is task running?
     * @var boolean
     */
    protected $isStopped = false;

    /**
     * Gaps is 2
     *
     * @var array
     */
    protected static $columnWidths = [
        'instance'    => 20,
        'usage'       => 5,
        'hit'         => 5,
        'connections' => 5,
        'time'        => 6,
        'evictions'   => 7,
        'get'         => 6,
        'set'         => 6,
        'read'        => 6,
        'write'       => 7,
    ];

    /**
     * Get connections.
     *
     * @return array
     *   List of \In2pire\Memcached\Client
     */
    protected function getConnections()
    {
        return $this->data['connections'];
    }

    /**
     * Handle system signal.
     *
     * @param int $signal
     *   Signal.
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *   Output.
     */
    protected function handleSignal($signal, OutputInterface $output)
    {
        // Clear last 2 characters
        $output->write("\033[2D");
        $output->write("\033[K");

        switch($signal) {
            case SIGTERM:
            case SIGKILL:
            case SIGINT:
                $this->isStopped = true;
                break;

            default:
                $this->isStopped = false;
                break;
        }
    }

    /**
     * Format a string.
     *
     * @param string $string
     *   String.
     * @param int $columnWidth
     *   Column width.
     * @param string $alignment
     *   Alignment.
     * @return string
     *   Formatted string.
     */
    protected function formatString($string, $columnWidth, $alignment = 'left')
    {
        switch($alignment) {
            case static::ALIGN_RIGHT:
                return sprintf('%' . $columnWidth . 's', $string);

            case static::ALIGN_CENTER:
                $left = $columnWidth - mb_strlen($string);

                if ($left < 0) {
                    return $string;
                }

                $left = floor($left / 2);
                return sprintf('%' . $left . 's', $string);

            case static::ALIGN_LEFT:
            default:
                $left = $columnWidth - mb_strlen($string);

                if ($left < 0) {
                    return $string;
                }

                return $string . str_repeat(' ', $left);
        }
    }

    /**
     * Format a number.
     *
     * @param int|float|double $number
     *   Number.
     *
     * @return float
     *   Formatted number.
     */
    protected function formatNumber($number)
    {
        if ($number < 1024) {
            return $number;
        }

        $number /= 1024.0;

        if ($number < 1024) {
            return sprintf('%.1fK', $number);
        }

        $number /= 1024.0;

        if ($number < 1024) {
            return sprintf('%.1fM', $number);
        }

        return sprintf('%.1fG', $number / 1024.0);
    }

    /**
     * Get statistics.
     *
     * @param array $connections
     *   List of \In2pire\Memcached\Client
     * @return array
     *   Server statistics
     */
    protected function getStats($connections)
    {
        $rawStats = [];

        // Get stats.
        foreach ($connections as $connection) {
            $id = (string) $connection;
            $t0 = microtime(true);

            if ($stats = $connection->getStats()) {
                $t1 = microtime(true) - $t0;
                $stats['connection_time'] = $t1;
                $rawStats[$id] = $stats;
            } else {
                $rawStats[$id] = false;
            }
        }

        return $rawStats;
    }

    /**
     * Analyze statistics.
     *
     * @param array $rawStats
     *   Raw statistics.
     * @param int $freq
     *   Refresh frequency.
     *
     * @return array
     *   Analyzed statistics.
     */
    protected function analyzeStats($rawStats, $freq)
    {
        static $lastRawStats = [];
        $analyzedStats = [];

        foreach ($rawStats as $key => $stats) {
            if ($stats === false) {
                $analyzedStats[$key] = false;
            } else {
                $hasStats = true;
                $lastStats = empty($lastRawStats[$key]) ? NULL : $lastRawStats[$key];

                // Usage.
                $limitMaxBytes = empty($stats['limit_maxbytes']) ? 0 : $stats['limit_maxbytes'];
                $bytes = $stats['bytes'];

                if (empty($limitMaxBytes)) {
                    $usage = 'UNLD';
                } else {
                    $usage = $bytes * 100 / $limitMaxBytes;
                }

                // Hit.
                if (empty($stats['cmd_get'])) {
                    $hit = 0;
                } else {
                    $hit = $stats['get_hits'] * 100 / $stats['cmd_get'];
                }

                // Connections.
                $connections = $stats['curr_connections'];

                // Time.
                $time = $stats['connection_time'];

                // Evictions.
                if (!isset($lastStats['evictions']) || $lastStats['evictions'] > $stats['evictions']) {
                    // Couldn't detect last evictions or server was restarted.
                    $evictions = null;
                } elseif ($lastStats['evictions']) {
                    $evictions = ($stats['evictions'] - $lastStats['evictions']) / $freq;
                } else {
                    $evictions = 0;
                }

                // Get.
                if (!isset($lastStats['cmd_get']) || $lastStats['cmd_get'] > $stats['cmd_get']) {
                    // Couldn't detect last cmd_get or server was restarted.
                    $get = null;
                } elseif ($lastStats['cmd_get']) {
                    $get = ($stats['cmd_get'] - $lastStats['cmd_get']) / $freq;
                } else {
                    $get = 0;
                }

                // Set.
                if (!isset($lastStats['cmd_set']) || $lastStats['cmd_set'] > $stats['cmd_set']) {
                    // Couldn't detect last cmd_set or server was restarted.
                    $set = null;
                } elseif ($lastStats['cmd_set']) {
                    $set = ($stats['cmd_set'] - $lastStats['cmd_set']) / $freq;
                } else {
                    $set = 0;
                }

                // Read.
                if (!isset($lastStats['bytes_read']) || $lastStats['bytes_read'] > $stats['bytes_read']) {
                    // Couldn't detect last bytes_read or server was restarted.
                    $read = null;
                } elseif ($lastStats['bytes_read']) {
                    $read = ($stats['bytes_read'] - $lastStats['bytes_read']) / $freq;
                } else {
                    $read = 0;
                }

                // Write.
                if (!isset($lastStats['bytes_written']) || $lastStats['bytes_written'] > $stats['bytes_written']) {
                    // Couldn't detect last bytes_written or server was restarted.
                    $write = null;
                } elseif ($lastStats['bytes_written']) {
                    $write = ($stats['bytes_written'] - $lastStats['bytes_written']) / $freq;
                } else {
                    $write = 0;
                }

                $analyzedStats[$key] = [
                    'bytes'       => $bytes,
                    'maxbytes'    => $limitMaxBytes,
                    'usage'       => $usage,
                    'hit'         => $hit,
                    'connections' => $connections,
                    'time'        => $time,
                    'evictions'   => $evictions,
                    'get'         => $get,
                    'set'         => $set,
                    'read'        => $read,
                    'write'       => $write,
                ];
            }
        }

        $lastRawStats = $rawStats;

        return $analyzedStats;
    }

    /**
     * Get console width.
     *
     * @return int
     *   Console width.
     */
    protected function getConsoleWidth()
    {
        static $app = null;

        if (null === $app) {
            $app = $this->getCommand()->getApplication();
        }

        return $app->getTerminalDimensions()[0];
    }

    /**
     * Render statistics.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *   Output.
     * @param array $connections
     *   List of \In2pire\Memcached\Client
     * @param int $freq
     *   Refresh frequency.
     * @param int $clear
     *   Clear screen.
     */
    protected function renderStats(OutputInterface $output, array $connections, $freq, $clear = true)
    {
        $stats = $this->getStats($connections);
        $analyzedStats = $this->analyzeStats($stats, $freq);
        $lines = [];
        $gaps = '  ';
        $pow1024 = 1024 * 1024;
        $totalStats = [];
        $hasStats = false;

        // Detect instance width.
        static $consoleWidth = null;
        static $instanceWidth = null;

        if ($consoleWidth != ($newConsoleWidth = $this->getConsoleWidth())) {
            $consoleWidth = $newConsoleWidth;
            unset(static::$columnWidths['instance']);
            $instanceWidth = $consoleWidth - array_sum(static::$columnWidths) - count(static::$columnWidths) * 2;
            $instanceWidth = min(22, max($instanceWidth, 8));
        }

        static::$columnWidths['instance'] = $instanceWidth;

        $lines[] = '<info>[' . date('Y-m-d H:i:s') . ']</info>';
        $lines[] = '';

        // Render headers.
        $headers[] = $this->formatstring('INSTANCE', static::$columnWidths['instance']);
        $headers[] = $this->formatstring('USAGE', static::$columnWidths['usage']);
        $headers[] = $this->formatstring('HIT %', static::$columnWidths['hit']);
        $headers[] = $this->formatstring('CONN', static::$columnWidths['connections']);
        $headers[] = $this->formatstring('TIME', static::$columnWidths['time']);
        $headers[] = $this->formatstring('EVICT/s', static::$columnWidths['evictions']);
        $headers[] = $this->formatstring('GETS/s', static::$columnWidths['get']);
        $headers[] = $this->formatstring('SETS/s', static::$columnWidths['set']);
        $headers[] = $this->formatstring('READ/s', static::$columnWidths['read']);
        $headers[] = $this->formatstring('WRITE/s', static::$columnWidths['write']);
        $lines[] = '<bold>' . implode($gaps, $headers) . '</bold>';

        foreach ($analyzedStats as $id => $stats) {
            $columns = [];
            $idLength = strlen($id);

            if ($idLength > static::$columnWidths['instance']) {
                list($host, $port) = explode(':', $id);
                $len = static::$columnWidths['instance'] - 3;
                $id = substr($host, 0, $len) . '...';
            }

            $columns[] = $this->formatstring($id, static::$columnWidths['instance']);

            if ($stats === false) {
                $columns[] = '<red>DOWN</red>';
            } else {
                // Prepare data.
                $usage       = $stats['usage'] == 'UNLD' ? $stats['usage'] : sprintf('%.1f%%', $stats['usage']);
                $hit         = sprintf('%.1f%%', $stats['hit']);
                $connections = $this->formatNumber($stats['connections']);
                $time        = sprintf('%.1fms', $stats['time']);

                if ($stats['evictions'] === null) {
                    $evictions = '';
                } else {
                    $evictions = $this->formatNumber($stats['evictions']);
                }

                if ($stats['get'] === null) {
                    $get = '';
                } else {
                    $get = $this->formatNumber($stats['get']);
                }

                if ($stats['set'] === null) {
                    $set = '';
                } else {
                    $set = $this->formatNumber($stats['set']);
                }

                if ($stats['read'] === null) {
                    $read = '';
                } else {
                    $read = $this->formatNumber($stats['read']);
                }

                if ($stats['write'] === null) {
                    $write = '';
                } else {
                    $write = $this->formatNumber($stats['write']);
                }

                // Render data.
                $columns[] = $this->formatstring($usage, static::$columnWidths['usage']);
                $columns[] = $this->formatstring($hit, static::$columnWidths['hit']);
                $columns[] = $this->formatstring($connections, static::$columnWidths['connections']);
                $columns[] = $this->formatstring($time, static::$columnWidths['time']);
                $columns[] = $this->formatstring($evictions, static::$columnWidths['evictions']);
                $columns[] = $this->formatstring($get, static::$columnWidths['get']);
                $columns[] = $this->formatstring($set, static::$columnWidths['set']);
                $columns[] = $this->formatstring($read, static::$columnWidths['read']);
                $columns[] = $this->formatstring($write, static::$columnWidths['write']);

                // Total & Average.
                if ($stats['usage'] != 'UNLD') {
                    $totalStats['bytes'][] = $stats['bytes'];
                    $totalStats['maxbytes'][] = $stats['maxbytes'];
                }

                $totalStats['usage'][] = $stats['usage'];
                $totalStats['hit'][] = $stats['hit'];
                $totalStats['connections'][] = $stats['connections'];
                $totalStats['time'][] = $stats['time'];
                $totalStats['evictions'][] = $stats['evictions'];
                $totalStats['get'][] = $stats['get'];
                $totalStats['set'][] = $stats['set'];
                $totalStats['read'][] = $stats['read'];
                $totalStats['write'][] = $stats['write'];

                $hasStats = true;
            }

            $lines[] = implode($gaps, $columns);
        }

        // Render other stats.
        if ($hasStats) {
            $totalUsage       = array_sum($totalStats['usage']);
            $totalHit         = array_sum($totalStats['hit']);
            $totalConnections = array_sum($totalStats['connections']);
            $totalTime        = array_sum($totalStats['time']);
            $totalEvictions   = array_sum($totalStats['evictions']);
            $totalGet         = array_sum($totalStats['get']);
            $totalSet         = array_sum($totalStats['set']);
            $totalRead        = array_sum($totalStats['read']);
            $totalWrite       = array_sum($totalStats['write']);

            $avgUsage       = sprintf('%.1f%%', $totalUsage / count($totalStats['usage']));
            $avgHit         = sprintf('%.1f%%', $totalHit / count($totalStats['hit']));
            $avgConnections = $this->formatNumber(round($totalConnections / count($totalStats['connections']), 1));
            $avgTime        = sprintf('%.1fms', $totalTime / count($totalStats['time']));
            $avgEvictions   = $this->formatNumber(round($totalEvictions / count($totalStats['evictions']), 1));
            $avgGet         = $this->formatNumber(round($totalGet / count($totalStats['get']), 1));
            $avgSet         = $this->formatNumber(round($totalSet / count($totalStats['set']), 1));
            $avgRead        = $this->formatNumber(round($totalRead / count($totalStats['read']), 1));
            $avgWrite       = $this->formatNumber(round($totalWrite / count($totalStats['write']), 1));

            $totalConnections = $this->formatNumber($totalConnections);
            $totalTime        = sprintf('%.1fms', $totalTime);
            $totalEvictions   = $this->formatNumber($totalEvictions);
            $totalGet         = $this->formatNumber($totalGet);
            $totalSet         = $this->formatNumber($totalSet);
            $totalRead        = $this->formatNumber($totalRead);
            $totalWrite       = $this->formatNumber($totalWrite);

            // Average.
            $lines[] = '';
            $columns = [];
            $columns[] = '<bold>' . $this->formatstring('AVERAGE:', static::$columnWidths['instance']) . '</bold>';
            $columns[] = $this->formatstring($avgUsage, static::$columnWidths['usage']);
            $columns[] = $this->formatstring($avgHit, static::$columnWidths['hit']);
            $columns[] = $this->formatstring($avgConnections, static::$columnWidths['connections']);
            $columns[] = $this->formatstring($avgTime, static::$columnWidths['time']);
            $columns[] = $this->formatstring($avgEvictions, static::$columnWidths['evictions']);
            $columns[] = $this->formatstring($avgGet, static::$columnWidths['get']);
            $columns[] = $this->formatstring($avgSet, static::$columnWidths['set']);
            $columns[] = $this->formatstring($avgRead, static::$columnWidths['read']);
            $columns[] = $this->formatstring($avgWrite, static::$columnWidths['write']);

            $lines[] = implode($gaps, $columns);

            // Total.
            $lines[] = '';
            $columns = [];
            $totalLabelWidth = static::$columnWidths['instance'];
            $totalUsageWidth = static::$columnWidths['usage'];
            $totalUsage = '';

            if (!empty($totalStats['bytes'])) {
                $totalUsage = $this->formatNumber(array_sum($totalStats['bytes'])) . 'B / ' . $this->formatNumber(array_sum($totalStats['maxbytes'])) . 'B';
                $totalLabelWidth = 6;
                $totalUsageWidth = static::$columnWidths['instance'] + static::$columnWidths['usage'] - 6;
            }

            $columns[] = '<bold>' . $this->formatstring('TOTAL:', $totalLabelWidth) . '</bold>';
            $columns[] = $this->formatstring($totalUsage, $totalUsageWidth, static::ALIGN_RIGHT);
            $columns[] = $this->formatstring(null, static::$columnWidths['hit']);
            $columns[] = $this->formatstring($totalConnections, static::$columnWidths['connections']);
            $columns[] = $this->formatstring($totalTime, static::$columnWidths['time']);
            $columns[] = $this->formatstring($totalEvictions, static::$columnWidths['evictions']);
            $columns[] = $this->formatstring($totalGet, static::$columnWidths['get']);
            $columns[] = $this->formatstring($totalSet, static::$columnWidths['set']);
            $columns[] = $this->formatstring($totalRead, static::$columnWidths['read']);
            $columns[] = $this->formatstring($totalWrite, static::$columnWidths['write']);

            $lines[] = implode($gaps, $columns);
        }

        $lines[] = '<info>(ctrl-c to quit.)</info>';

        // Init buffered output.
        $buffer = new BufferedOutput($output->getVerbosity(), $output->isDecorated(), $output->getFormatter());

        // Render output to buffer.
        foreach ($lines as $line) {
            $buffer->writeln($line);
        }

        $content = $buffer->fetch();

        // Reset screen.
        if ($clear) {
            echo "\033[2J\033[1;1H";
        }

        // Show new stats.
        echo $content;

        unset($content, $buffer, $lines, $headers, $columns);
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // Validation.
        $consoleWidth = $this->getConsoleWidth();

        if (empty($consoleWidth)) {
            throw new \RuntimeException('Could not detect terminal width');
        }

        // Prepare variables.
        $connections = $this->getConnections();
        $freq = (float) $input->getOption('freq');

        if ($freq < 0.5) {
            $freq = 0.5;
        }

        // Prepare styles
        $lightRedStyle = new OutputFormatterStyle('red', null, array('bold'));
        $redStyle      = new OutputFormatterStyle('red');
        $boldStyle     = new OutputFormatterStyle(null, null, array('bold'));
        $output->getFormatter()->setStyle('light-red', $lightRedStyle);
        $output->getFormatter()->setStyle('red', $redStyle);
        $output->getFormatter()->setStyle('bold', $boldStyle);

        // Prepare main process.
        declare(ticks=1);

        $signalHandler = function ($signal) use ($output) {
            $this->handleSignal($signal, $output);
        };

        pcntl_signal(SIGTERM, $signalHandler);
        pcntl_signal(SIGINT, $signalHandler);

        // Prepare other information.
        $stats = $this->getStats($connections);
        $this->analyzeStats($stats, $freq);
        sleep(1);

        // Ready to start.
        $this->isStopped = false;
        $sleep = $freq * 1000000;

        while(!$this->isStopped) {
            // Save screen.
            echo "\e[?1049h";
            // Render stats.
            $this->renderStats($output, $connections, $freq, true);
            // Sleep baby, sleep. Sleeping beauty.
            usleep($sleep);
            // Restore screen.
            echo "\e[?1049l";
        }

        // Render stats one last time.
        $this->renderStats($output, $connections, $freq, false);
    }
}
