<?php

namespace Server\Helper;

use PhpTus\Server\Helper\ResponseHelper;
use Symfony\Component\HttpFoundation\Response;

class ResponseHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testResponseWithoutExtensionAndMaxSize()
    {
        $response = new Response();

        $helper = new ResponseHelper($response, [], null);
        $helper->addCommonHeaders();

        $this->assertEquals(ResponseHelper::VERSION, $response->headers->get('Tus-Resumable'));
        $this->assertEquals(ResponseHelper::VERSION, $response->headers->get('Tus-Version'));
        $this->assertNull($response->headers->get('Tus-Extension'));
        $this->assertNull($response->headers->get('Tus-Max-Size'));
    }

    public function testResponseWithExtensions()
    {
        $response = new Response();

        $helper = new ResponseHelper($response, ['extension1', 'extension2'], null);
        $helper->addCommonHeaders();
        $this->assertEquals('extension1,extension2', $response->headers->get('Tus-Extension'));
    }

    public function testResponseWithMaxSize()
    {
        $response = new Response();

        $helper = new ResponseHelper($response, [], 1);
        $helper->addCommonHeaders();
        $this->assertEquals('1', $response->headers->get('Tus-Max-Size'));
    }
}
