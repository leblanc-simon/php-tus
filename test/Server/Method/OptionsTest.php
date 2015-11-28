<?php

namespace Server\Method;

use PhpTus\Server\Method\Options;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;

class OptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testSendNoContent()
    {
        $headers = new HeaderBag();
        $options = new Options($headers);

        $response = $options->process();

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }
}
