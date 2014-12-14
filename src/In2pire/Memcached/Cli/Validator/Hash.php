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
 * Validator for hash function
 */
class Hash extends \In2pire\Cli\Validator\CliValidator
{
    /**
     * Possible hash functions.
     *
     * @var array
     */
    protected $functions = ['md5', 'sha1'];

    /**
     * @inheritdoc
     */
    public function validate(InputInterface $input)
    {
        $hash = $input->getOption('hash');

        if (!empty($hash) && !in_array($hash, $this->functions)) {
            throw new \UnexpectedValueException('Invalid hash function');
        }

        return [
            'hash' => $hash,
        ];
    }
}
