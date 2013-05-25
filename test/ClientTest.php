<?php
/**
 * This file is part of the PhpTus package.
 *
 * (c) Simon Leblanc <contact@leblanc-simon.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class ClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers  \PhpTus\Client::upload
     * @covers  \PhpTus\Client::getPost
     * @covers  \PhpTus\Client::getLocation
     * @covers  \PhpTus\Client::getHead
     * @covers  \PhpTus\Client::getOffset
     * @covers  \PhpTus\Client::patch
     * @covers  \PhpTus\Client::setFilename
     * @covers  \PhpTus\Client::setEndPoint
     * @covers  \PhpTus\Client::getFingerprint
     */
    public function testUpload()
    {
        $client = new PhpTus\Client();
        
        $this->assertInstanceOf('\\PhpTus\\Client', $client->setFilename(__DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'file-to-upload'));
        $this->assertInstanceOf('\\PhpTus\\Client', $client->setEndPoint('http://master.tus.io/files/'));

        $this->assertNull($client->getFingerprint());

        // Send a first part of the file
        $this->assertTrue($client->upload(90));

        $this->assertRegExp('/[a-f0-9]{32}/', $client->getFingerprint());

        // Send the remaining content 
        $this->assertTrue($client->upload());
    }

    /**
     * @expectedException   DomainException
     * @expectedExceptionMessage    Filesize can't be null when call PhpTus\Client::getPost
     * @covers  \PhpTus\Client::upload
     * @covers  \PhpTus\Client::getPost
     * @covers  \PhpTus\Client::getLocation
     */
    public function testFailFilename()
    {
        $client = new PhpTus\Client();
        $client->setEndPoint('http://master.tus.io/files/');

        $client->upload(1);
    }

    /**
     * @expectedException   DomainException
     * @expectedExceptionMessage    End-point can't be null when call PhpTus\Client::getPost
     * @covers  \PhpTus\Client::upload
     * @covers  \PhpTus\Client::getPost
     * @covers  \PhpTus\Client::getLocation
     */
    public function testFailEndPoint()
    {
        $client = new PhpTus\Client();
        $client->setFilename(__DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'file-to-upload');

        $client->upload(1);
    }

    /**
     * @expectedException   PhpTus\Exception\File
     * @covers  \PhpTus\Client::setFilename
     */
    public function testFailBadFilename()
    {
        $client = new PhpTus\Client();
        $client->setFilename(__DIR__.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'file-to-upload'.mt_rand());
    }
}