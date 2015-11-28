<?php

namespace PhpTus\Server\Extension\Checksum;

use PhpTus\Exception\Rfc as RfcException;

abstract class ChecksumAbstract
{
    /**
     * Return the list of available hash algorithms
     *
     * @return array
     * @throws RfcException
     */
    protected function getAvailableAlgorithms()
    {
        $algorithms = hash_algos();
        if (false === in_array('sha1', $algorithms)) {
            throw new RfcException('The Server MUST support at least the SHA1 checksum algorithm identified by sha1');
        }

        // RFC : algorithm must contains only ASCII character expect uppercase
        // --> remove all algoritm which don't respect the rule
        $iterator = count($algorithms) - 1;
        do {
            if (preg_match('/^[a-z0-9]+$/', $algorithms[$iterator]) === 0) {
                unset($algorithms[$iterator]);
            }
            $iterator--;
        } while ($iterator >= 0);

        return $algorithms;
    }
}