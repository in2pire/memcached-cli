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
 * Validator for memcached slab id.
 */
class MemcachedSlab
{
    /**
     * @inheritdoc
     */
    public function validate(InputInterface $input)
    {
        $slab = $input->getArgument('slab');

        if (!empty($slab) && !preg_match('#^\d+$#', $slab)) {
            throw new \UnexpectedValueException('Slab ID must be an integer');
        }

        return [
            'slab' => $slab
        ];
    }
}
