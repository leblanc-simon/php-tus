<?php
/**
 * This file is part of the PhpTus package.
 *
 * (c) Simon Leblanc <contact@leblanc-simon.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class ServerTest extends PHPUnit_Framework_TestCase
{
    protected static $location = null;

    public function testOptions()
    {
        $_SERVER['HTTP_Final-Length'] = 180;
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;

        $server = new PhpTus\Server(__DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'server', 
                                    '/files/', 
                                    array('prefix' => 'php-tus-test-')
        );

        $response = $server->process(false);
        
        $this->assertInstanceOf('\\Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Allow'));
        $this->assertRegExp('#^OPTIONS,GET,HEAD,POST,PATCH$#', $response->headers->get('Allow'));
    }


    public function testPost()
    {
        $_SERVER['HTTP_Final-Length'] = 180;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;

        $server = new PhpTus\Server(__DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'server', 
                                    '/files/', 
                                    array('prefix' => 'php-tus-test-')
        );

        $response = $server->process(false);
        
        $this->assertInstanceOf('\\Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Location'));
        $this->assertRegExp('#^http://localhost/files/[a-z0-9]{64}$#', $response->headers->get('Location'));

        $location = $response->headers->get('Location');

        return $location;
    }


    /**
     * @depends     testPost
     */
    public function testHead($location)
    {
        $_SERVER['REQUEST_METHOD'] = 'HEAD';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['REQUEST_URI'] = str_replace('http://localhost', '', $location);

        $server = new PhpTus\Server(__DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'server', 
                                    '/files/', 
                                    array('prefix' => 'php-tus-test-')
        );

        $response = $server->process(false);

        $this->assertInstanceOf('\\Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Offset'));
        $this->assertEquals(0, $response->headers->get('Offset'));
    }


    /**
     * @depends     testPost
     */
    public function testGet($location)
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['REQUEST_URI'] = str_replace('http://localhost', '', $location);

        $server = new PhpTus\Server(__DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'server', 
                                    '/files/', 
                                    array('prefix' => 'php-tus-test-')
        );

        $response = $server->process(false);

        $this->assertInstanceOf('\\Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Content-disposition'));
        $this->assertEquals('attachment; filename="'.basename($location).'"', $response->headers->get('Content-disposition'));
    }


    /**
     * @depends     testPost
     * @expectedException   \PhpTus\Exception\Request
     * @expectedExceptionCode   404
     */
    public function testFailGet($location)
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['REQUEST_URI'] = str_replace('http://localhost', '', substr($location, 0, -1));

        $server = new PhpTus\Server(__DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'server', 
                                    '/files/', 
                                    array('prefix' => 'php-tus-test-')
        );

        $response = $server->process(false);
    }
}