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

/**
 * Validator for statistics.
 */
class StatisticsFormatter extends \In2pire\Cli\Validator\CliValidator
{
    /**
     * @inheritdoc
     */
    public function validate(InputInterface $input)
    {
        $format = $input->getOption('format');

        if ('table' != $format && 'json' != $format) {
            throw new \UnexpectedValueException('Invalid format');
        }

        return [
            'format' => $format,
        ];
    }
}
