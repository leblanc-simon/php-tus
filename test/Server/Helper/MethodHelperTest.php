<?php

namespace Server\Helper;

use PhpTus\Server\Helper\MethodHelper;
use Symfony\Component\HttpFoundation\HeaderBag;

class MethodHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testWhileHaveOnlyMethod()
    {
        $helper = new MethodHelper('POST', new HeaderBag());
        $this->assertEquals('POST', $helper->getMethodName());
    }

    public function testOverrideMethod()
    {
        $headers = new HeaderBag();
        $headers->add([
            MethodHelper::HEADER_METHOD => 'PATCH',
        ]);

        $helper = new MethodHelper('POST', $headers);

        $this->assertEquals('PATCH', $helper->getMethodName());
    }
}
