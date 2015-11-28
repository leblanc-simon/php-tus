<?php

namespace PhpTus\Server\Extension;

/**
 * Interface DefinitionInterface
 *
 * @package PhpTus\Server\Extension
 */
interface DefinitionInterface
{
    /**
     * @return string the name of the extension
     */
    public function getName();

    /**
     * @return ExtensionInterface[] the list of object to manage the extension
     */
    public function getClasses();
}