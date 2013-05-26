<?php
/**
 * This file is part of the PhpTus package.
 *
 * (c) Simon Leblanc <contact@leblanc-simon.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpTus;

/**
 * Client for upload a resumable upload
 *
 * $tus = new PhpTus\Client();
 * $tus->setFilename('my-file.bin');
 * $tus->setEndPoint('http://example.com/');
 * $tus->upload(30);
 * $tus->upload(60);
 *
 */
class Client
{
    const TIMEOUT = 30;
    
    private $filename = null;
    private $end_point = null;
    private $fingerprint = null;
    
    private $filesize = null;
    
    private $current_offset = null;
    private $length = null;
    
    private $location = null;
    

    /**
     * Constructor
     *
     * @param       string      $filename       The filename to upload
     * @param       string      $end_point      The end point where you upload the file
     * @param       string      $fingerprint    The fingerprint of the upload
     * @throws      \PhpTus\Exception\Required  if curl extension isn't loaded
     * @access      public
     */
    public function __construct($filename = null, $end_point = null, $fingerprint = null)
    {
        if (function_exists('curl_init') === false) {
            throw new Exception\Required('cURL is required');
        }
        
        if ($filename !== null) {
            $this->setFilename($filename);
        }
        
        if ($end_point !== null) {
            $this->setEndPoint($end_point);
        }
        
        if ($fingerprint !== null) {
            $this->setFingerprint($fingerprint);
        }
    }
    

    /**
     * Upload part of the file
     *
     * @param       int     $length     The length of the file to upload (null for send all)
     * @return      bool                True in success
     * @access      public
     */
    public function upload($length = null)
    {
        if ($this->fingerprint === null) {
            $this->getLocation();
            $this->current_offset = 0;
        } else {
            $this->getLocation();
            $this->getOffset();
        }

        if ($length === null) {
            $this->length = $this->filesize;
        } elseif (is_numeric($length) === false) {
            throw new \InvalidArgumentException('length parameter must be an integer');
        } else {
            $this->length = $length;
        }
        
        $this->patch();

        return true;
    }
    

    /**
     * Get the current filename
     *
     * @return      string|null     the current filename, null if no filename initialize
     * @access      public
     */
    public function getFilename()
    {
        return $this->filename;
    }
    

    /**
     * Initialize the filename (file to upload)
     *
     * @param       string      $filename       The filename to upload
     * @return      \PhpTus\Client              The current Client instance
     * @throws      \InvalidArgumentException   if filename isn't a string
     * @throws      \PhpTus\Exception\File      if the filename doesn't exist
     * @throws      \PhpTus\Exception\File      if it's impossible to get filesize
     * @access      public
     */
    public function setFilename($filename)
    {
        if (is_string($filename) === false) {
            throw new \InvalidArgumentException('filename parameter must be a string');
        }
        
        if (file_exists($filename) === false || is_readable($filename) === false) {
            throw new Exception\File('Can\'t read file : '.$filename);
        }
        
        $filesize = filesize($filename);
        
        if ($filesize === false) {
            throw new Exception\File('Impossible to get the filesize of '.$filename);
        }
        
        $this->filename = $filename;
        $this->filesize = $filesize;
        
        return $this;
    }
    
    
    /**
     * Get the current end point
     *
     * @return      string|null     the current end point, null if no end point initialize
     * @access      public
     */
    public function getEndPoint()
    {
        return $this->end_point;
    }
    

    /**
     * Initialize the end point (location to upload)
     *
     * @param       string      $end_point      The end point where to upload file
     * @return      \PhpTus\Client              The current Client instance
     * @throws      \InvalidArgumentException   if end point isn't a string
     * @access      public
     */
    public function setEndPoint($end_point)
    {
        if (is_string($end_point) === false) {
            throw new \InvalidArgumentException('end_point parameter must be a string');
        }
        
        $this->end_point = $end_point;
        
        return $this;
    }
    
    
    /**
     * Get the current fingerprint
     *
     * @return      string|null     the current fingerprint, null if no fingerprint initialize
     * @access      public
     */
    public function getFingerprint()
    {
        return $this->fingerprint;
    }
    

    /**
     * Initialize the fingerprint (to resume an upload)
     *
     * @param       string      $fingerprint    The fingerprint to use for resume upload
     * @return      \PhpTus\Client              The current Client instance
     * @throws      \InvalidArgumentException   if fingerprint isn't a string or null
     * @access      public
     */
    public function setFingerprint($fingerprint)
    {
        if (is_string($fingerprint) === false && is_null($fingerprint) === false) {
            throw new \InvalidArgumentException('fingerprint parameter must be a string or null');
        }
        
        $this->fingerprint = $fingerprint;
        
        return $this;
    }
    

    /**
     * Call the POST request to initialize the upload
     *
     * @return      array                       The array with the header's informations of the response
     * @throws      \DomainException            If the end-point isn't define
     * @throws      \DomainException            If the filesize isn't define
     * @throws      \PhpTus\Exception\Curl      If the curl request fail
     * @throws      \PhpTus\Exception\BadHeader If the response return an unexcepted HTTP Code
     * @access      private
     */
    private function getPost()
    {
        if ($this->end_point === null) {
            throw new \DomainException('End-point can\'t be null when call '.__METHOD__);
        }
        
        if ($this->filesize === null) {
            throw new \DomainException('Filesize can\'t be null when call '.__METHOD__);
        }
        
        $handle = curl_init();
        $custom_headers = array();
        
        curl_setopt($handle, CURLOPT_URL, $this->end_point);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HEADER, false);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_FAILONERROR, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
        curl_setopt($handle, CURLOPT_BUFFERSIZE, 64000);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array(
            'Content-Length: 0',
            'Final-Length: '.$this->filesize,
        ));
        curl_setopt($handle, CURLOPT_HEADERFUNCTION, function($ch, $header) use (&$custom_headers){
            $headers = array();
            $extract = explode("\r\n", $header);
            foreach ($extract as $line) {
                if (preg_match('/^([a-z0-9_-]+): (.*)$/i', $line, $matches)) {
                    list($null, $key, $value) = $matches;
                    $headers[$key] = $value;
                }
            }

            $custom_headers = array_merge($custom_headers, $headers);

            return strlen($header);
        });
        
        if (curl_exec($handle) === false) {
            throw new Exception\Curl('Error while request POST into '.$this->end_point.' : '.curl_error($handle), curl_errno($handle));
        }
        
        $http_code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        
        if ($http_code !== 201) {
            throw new Exception\BadHeader('Error while request POST - excepted HTTP Code 201 - get HTTP Code '.$http_code);
        }
        
        $info = array_merge($custom_headers, curl_getinfo($handle));
        curl_close($handle);
        
        return $info;
    }
    

    /**
     * Get the location where the file is sent
     *
     * @return  string      The current location where the file is sent
     * @access  private
     */
    private function getLocation()
    {
        $this->location = null;
        
        if ($this->fingerprint !== null && $this->end_point !== null) {
            $this->location = $this->end_point.$this->fingerprint;
        } else {
            $info = $this->getPost();
            
            if (isset($info['Location']) === true) {
                $this->fingerprint = str_replace($this->end_point, '', $info['Location']);
                $this->location = $info['Location'];
            }
        }
        
        return $this->location;
    }
    

    /**
     * Call the HEAD request to get the current status of the upload
     *
     * @return      array                       The array with the header's informations of the response
     * @throws      \DomainException            If the location isn't define
     * @throws      \PhpTus\Exception\Curl      If the curl request fail
     * @throws      \PhpTus\Exception\BadHeader If the response return an unexcepted HTTP Code
     * @access      private
     */
    private function getHead()
    {
        if ($this->location === null) {
            throw new \DomainException('Location can\'t be null when call '.__METHOD__);
        }
        
        $handle = curl_init();
        $custom_headers = array();
        
        curl_setopt($handle, CURLOPT_URL, $this->location);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_NOBODY, true);
        curl_setopt($handle, CURLOPT_FAILONERROR, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
        curl_setopt($handle, CURLOPT_BUFFERSIZE, 64000);
        curl_setopt($handle, CURLOPT_HEADERFUNCTION, function($ch, $header) use (&$custom_headers){
            $headers = array();
            $extract = explode("\r\n", $header);
            foreach ($extract as $line) {
                if (preg_match('/^([a-z0-9_-]+): (.*)$/i', $line, $matches)) {
                    list($null, $key, $value) = $matches;
                    $headers[$key] = trim($value);
                }
            }

            $custom_headers = array_merge($custom_headers, $headers);

            return strlen($header);
        });
        
        if (curl_exec($handle) === false) {
            throw new Exception\Curl('Error while request HEAD into '.$this->location.' : '.curl_error($handle), curl_errno($handle));
        }
        
        $http_code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        
        if ($http_code !== 200) {
            throw new Exception\BadHeader('Error while request POST - excepted HTTP Code 200 - get HTTP Code '.$http_code);
        }
        
        $info = array_merge($custom_headers, curl_getinfo($handle));
        curl_close($handle);
        
        return $info;
    }
    

    /**
     * Get the current offset of the upload (number of byte already sent)
     *
     * @return  int     The current offset of the upload
     * @access  private
     */
    private function getOffset()
    {
        $this->current_offset = null;
        
        $headers = $this->getHead();
        
        if (isset($headers['Offset']) === true) {
            $this->current_offset = $headers['Offset'];
        }
        
        return $this->current_offset;
    }
    

    /**
     * Call the PATCH request to upload the file
     *
     * @return      array                       The array with the header's informations of the response
     * @throws      \DomainException            If the location isn't define
     * @throws      \DomainException            If the filename isn't define
     * @throws      \DomainException            If the length isn't define
     * @throws      \DomainException            If the current_offset isn't define
     * @throws      \PhpTus\Exception\File      If it's impossible to read the file
     * @throws      \PhpTus\Exception\File      If it's impossible to move the pointer of the position in the file
     * @throws      \PhpTus\Exception\Curl      If the curl request fail
     * @throws      \PhpTus\Exception\BadHeader If the response return an unexcepted HTTP Code
     * @access      private
     */
    private function patch()
    {
        if ($this->location === null) {
            throw new \DomainException('Location can\'t be null when call '.__METHOD__);
        }
        
        if ($this->filename === null) {
            throw new \DomainException('Filename can\'t be null when call '.__METHOD__);
        }
        
        if ($this->length === null) {
            throw new \DomainException('Length can\'t be null when call '.__METHOD__);
        }
        
        if ($this->current_offset === null) {
            throw new \DomainException('Current offset can\'t be null when call '.__METHOD__);
        }

        // All content of the file is already sending : quit
        if ($this->current_offset >= $this->filesize) {
            return;
        }
        
        // If you want send more than the content, truncate the content
        if ($this->current_offset + $this->length > $this->filesize) {
            $this->length = $this->filesize - $this->current_offset;
        }
        
        $fhandle = fopen($this->filename, 'rb');
        if ($fhandle === false) {
            throw new Exception\File('Impossible to open file : '.$this->filename);
        }
        
        if (fseek($fhandle, $this->current_offset) === -1) {
            throw new Exception\File('Impossible to set the position of offset for file : '.$this->filename);
        }
        
        $length = $this->length;
        $data_sent = 0;
        $max_length = $length;
        
        $handle = curl_init();
        
        curl_setopt($handle, CURLOPT_URL, $this->location);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($handle, CURLOPT_UPLOAD, true);
        curl_setopt($handle, CURLOPT_FAILONERROR, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/offset+octet-stream',
            'Offset: '.$this->current_offset,
        ));
        
        curl_setopt($handle, CURLOPT_INFILE, $fhandle);
        curl_setopt($handle, CURLOPT_INFILESIZE, $this->length);
        curl_setopt($handle, CURLOPT_READFUNCTION, function($ch, $fh, $length) use (&$data_sent, $max_length) {
            if ($data_sent >= $max_length) {
                return '';
            }

            if ($data_sent + $length > $max_length) {
                $length = $max_length - $data_sent;
            }

            $data_sent += $length;

            return fread($fh, $length);
        });
        
        if (curl_exec($handle) === false) {
            throw new Exception\Curl('Error while request PATCH into '.$this->location.' : '.curl_error($handle), curl_errno($handle));
        }
        
        $http_code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        
        if ($http_code !== 200) {
            throw new Exception\BadHeader('Error while request PATCH - excepted HTTP Code 200 - get HTTP Code '.$http_code);
        }
    }
}
