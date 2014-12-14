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
 * Validator for data formatter.
 */
class DataFormatter extends \In2pire\Cli\Validator\CliValidator
{
    /**
     * Possible data formatters.
     *
     * @var array
     */
    protected $formatters = ['json', 'export', 'dump', 'serialize'];

    /**
     * @inheritdoc
     */
    public function validate(InputInterface $input)
    {
        $format = $input->getOption('format');

        if (!in_array($format, $this->formatters)) {
            throw new \UnexpectedValueException('Invalid format');
        }

        return [
            'format' => $format,
        ];
    }
}
