<?php

namespace PhpTus\Server\Extension\Checksum;

use PhpTus\Server\Extension\DefinitionInterface;

class Definition implements DefinitionInterface
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'checksum';
    }

    /**
     * @inheritdoc
     */
    public function getClasses()
    {
        return [
            new Options(),
            new Patch(),
        ];
    }
}
