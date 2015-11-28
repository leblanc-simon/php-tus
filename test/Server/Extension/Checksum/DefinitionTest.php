<?php

namespace Server\Extension\Checksum;

use PhpTus\Server\Extension\Checksum\Definition;

class DefinitionTest extends \PHPUnit_Framework_TestCase
{
    public function testNameMustBeValid()
    {
        $this->assertEquals('checksum', (new Definition())->getName());
    }

    public function testMustHaveValidProcessor()
    {
        $definition = new Definition();
        $processors = $definition->getClasses();

        $this->assertInternalType('array', $processors);
        $this->assertCount(2, $processors);
        foreach ($processors as $processor) {
            $this->assertInstanceOf('\PhpTus\Server\Extension\ExtensionInterface', $processor);
        }
    }
}
