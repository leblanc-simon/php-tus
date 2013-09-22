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

use Symfony\Component\HttpFoundation\Request as Request;
use Symfony\Component\HttpFoundation\Response as Response;

use Predis\Client as PredisClient;

class Server
{
    const TIMEOUT = 30;

    const POST      = 'POST';
    const HEAD      = 'HEAD';
    const PATCH     = 'PATCH';
    const OPTIONS   = 'OPTIONS';
    const GET       = 'GET';

    private $uuid       = null;
    private $directory  = null;
    private $path       = null;
    private $host       = null;

    private $request    = null;
    private $response   = null;

    private $redis          = null;
    private $redis_options  = array(
        'prefix'    => 'php-tus-',
        'scheme'    => 'tcp',
        'host'      => '127.0.0.1',
        'port'      => '6379',
    );


    /**
     * Constructor
     *
     * @param   string      $directory      The directory to use for save the file
     * @param   string      $path           The path to use in the URI
     * @param   null|array  $redis_options  Override the default Redis options
     * @access  public
     */
    public function __construct($directory, $path, $redis_options = null)
    {
        $this
            ->setDirectory($directory)
            ->setPath($path)
            ->setRedisOptions($redis_options);
    }


    /**
     * Process the client request
     *
     * @param   bool    $send                                   True to send the response, false to return the response
     * @return  void|Symfony\Component\HttpFoundation\Response  void if send = true else Response object
     * @throws  \PhpTus\Exception\Request                       If the method isn't available
     * @access  public
     */
    public function process($send = false)
    {
        try {
            $method = $this->getRequest()->getMethod();

            if ($method === self::OPTIONS) {
                $this->uuid = null;
            } elseif ($method === self::POST) {
                $this->buildUuid();
            } else {
                $this->getUserUuid();
            }

            switch ($method) {
                case self::POST:
                    $this->processPost();
                    break;

                case self::HEAD:
                    $this->processHead();
                    break;

                case self::PATCH:
                    $this->processPatch();
                    break;

                case self::OPTIONS:
                    $this->processOptions();
                    break;

                case self::GET:
                    $this->processGet($send);
                    break;

                default:
                    throw new Exception\Request('The requested method '.$method.' is not allowed', 405);
            }

            $this->addCommonHeader();

            if ($send === false) {
                return $this->response;
            }
        } catch (Exception\BadHeader $e) {
            if ($send === false) {
                throw $e;
            }

            $this->response = new Response(null, 400);
            $this->addCommonHeader();
        } catch (Exception\Request $e) {
            if ($send === false) {
                throw $e;
            }

            $this->response = new Response($e->getMessage(), $e->getCode());
            $this->addCommonHeader();
        } catch (\Exception $e) {
            if ($send === false) {
                throw $e;
            }

            $this->response = new Response(null, 500);
            $this->addCommonHeader();
        }

        $this->response->sendHeaders();
        
        // The process must only sent the HTTP headers : kill request after send
        exit;
    }


    /**
     * Build a new UUID (use in the POST request)
     *
     * @throws  \DomainException    If the path isn't define
     * @access  private
     */
    private function buildUuid()
    {
        if ($this->path === null) {
            throw new \DomainException('Path can\'t be null when call '.__METHOD__);
        }

        $this->uuid = $this->path.hash('sha256', uniqid(mt_rand().php_uname(), true));
    }


    /**
     * Get the UUID of the request (use for HEAD and PATCH request)
     *
     * @return  string                      The UUID of the request
     * @throws  \InvalidArgumentException   If the UUID doesn't match with the path
     * @access  private
     */
    private function getUserUuid()
    {
        if ($this->uuid === null) {
            $uuid = $this->getRequest()->getRequestUri();

            if (strpos($uuid, $this->path) !== 0) {
                throw new \InvalidArgumentException('The uuid and the path doesn\'t match : '.$uuid.' - '.$this->path);
            }

            $this->uuid = $uuid;
        }

        return $this->uuid;
    }


    /**
     * Process the POST request
     *
     * @throws  \Exception                      If the uuid already exists
     * @throws  \PhpTus\Exception\BadHeader     If the final length header isn't a positive integer
     * @throws  \PhpTus\Exception\File          If the file already exists in the filesystem
     * @throws  \PhpTus\Exception\File          If the creation of file failed
     * @access  private
     */
    private function processPost()
    {
        if ($this->existsInRedis($this->uuid) === true) {
            throw new \Exception('The UUID already exists');
        }

        $headers = $this->extractHeaders(array('Final-Length'));

        if (is_numeric($headers['Final-Length']) === false || $headers['Final-Length'] < 0) {
            throw new Exception\BadHeader('Final-Length must be a positive integer');
        }

        $final_length = (int)$headers['Final-Length'];

        $file = $this->directory.$this->getFilename();

        if (file_exists($file) === true) {
            throw new Exception\File('File already exists : '.$file);
        }

        if (touch($file) === false) {
            throw new Exception\File('Impossible to touch '.$file);
        }

        $this->setInRedis($this->uuid, 'Final-Length', $final_length);
        $this->setInRedis($this->uuid, 'Offset', 0);

        $this->response = new Response(null, 201, array(
            'Location' => $this->getRequest()->getSchemeAndHttpHost().$this->uuid,
        ));
    }


    /**
     * Process the HEAD request
     *
     * @throws  \Exception      If the uuid isn't know
     * @access  private
     */
    private function processHead()
    {
        if ($this->existsInRedis($this->uuid) === false) {
            throw new \Exception('The UUID doesn\'t exists');
        }

        $offset = $this->getInRedis($this->uuid, 'Offset');

        $this->response = new Response(null, 200, array(
            'Offset' => $offset,
        ));
    }


    /**
     * Process the PATCH request
     *
     * @throws  \Exception                      If the uuid isn't know
     * @throws  \PhpTus\Exception\BadHeader     If the Offset header isn't a positive integer
     * @throws  \PhpTus\Exception\BadHeader     If the Content-Length header isn't a positive integer
     * @throws  \PhpTus\Exception\BadHeader     If the Content-Type header isn't "application/offset+octet-stream"
     * @throws  \PhpTus\Exception\BadHeader     If the Offset header and Offset Redis are not equal
     * @throws  \PhpTus\Exception\Required      If the final length is smaller than offset
     * @throws  \PhpTus\Exception\File          If it's impossible to open php://input
     * @throws  \PhpTus\Exception\File          If it's impossible to open the destination file
     * @throws  \PhpTus\Exception\File          If it's impossible to set the position in the destination file
     */
    private function processPatch()
    {
        // Check the uuid
        if ($this->existsInRedis($this->uuid) === false) {
            throw new \Exception('The UUID doesn\'t exists');
        }

        // Check HTTP headers
        $headers = $this->extractHeaders(array('Offset', 'Content-Length', 'Content-Type'));

        if (is_numeric($headers['Offset']) === false || $headers['Offset'] < 0) {
            throw new Exception\BadHeader('Offset must be a positive integer');
        }

        if (is_numeric($headers['Content-Length']) === false || $headers['Content-Length'] < 0) {
            throw new Exception\BadHeader('Content-Length must be a positive integer');
        }

        if (is_string($headers['Content-Type']) === false || $headers['Content-Type'] !== 'application/offset+octet-stream') {
            throw new Exception\BadHeader('Content-Type must be "application/offset+octet-stream"');
        }

        // Initialize vars
        $offset_header = (int)$headers['Offset'];
        $offset_redis = $this->getInRedis($this->uuid, 'Offset');
        $max_length = $this->getInRedis($this->uuid, 'Final-Length');
        $content_length = (int)$headers['Content-Length'];

        // Check consistency (user vars vs database vars)
        if ($offset_redis === null || (int)$offset_redis !== $offset_header) {
            throw new Exception\BadHeader('Offset header isn\'t the same as in Redis');
        }
        if ($max_length === null || (int)$offset_redis > (int)$max_length) {
            throw new Exception\Required('Final-Length is required and must be greather than Offset');
        }

        // Check if the file isn't already entirely write
        if ((int)$offset_redis === (int)$max_length) {
            $this->response = new Response(null, 200);
            return;
        }

        // Read / Write datas
        $handle_input = fopen('php://input', 'rb');
        if ($handle_input === false) {
            throw new Exception\File('Impossible to open php://input');
        }

        $file = $this->directory.$this->getFilename();
        $handle_output = fopen($file, 'ab');
        if ($handle_output === false) {
            throw new Exception\File('Impossible to open file to write into');
        }

        if (fseek($handle_output, (int)$offset_redis) === false) {
            throw new Exception\File('Impossible to move pointer in the good position');
        }

        ignore_user_abort(true);

        $current_size = (int)$offset_redis;
        $total_write = 0;

        try {
            while (true) {
                set_time_limit(self::TIMEOUT);

                // Manage user abort
                if(connection_status() != CONNECTION_NORMAL) {
                    throw new Exception\Abort('User abort connexion');
                }
            
                $data = fread($handle_input, 8192);
                if ($data === false) {
                    throw new Exception\File('Impossible to read the datas');
                }

                $size_read = strlen($data);

                // If user sent more datas than expected (by POST Final-Length), abort
                if ($size_read + $current_size > $max_length) {
                    throw new Exception\Max('Size sent is greather than max length expected');
                }


                // If user sent more datas than expected (by PATCH Content-Length), abort
                if ($size_read + $total_write > $content_length) {
                    throw new Exception\Max('Size sent is greather than max length expected');
                }

                // Write datas
                $size_write = fwrite($handle_output, $data);
                if ($size_write === false) {
                    throw new Exception\File('Impossible to write the datas');
                }

                $current_size += $size_write;
                $total_write += $size_write;
                $this->setInRedis($this->uuid, 'Offset', $current_size);

                if ($total_write === $content_length) {
                    fclose($handle_input);
                    fclose($handle_output);
                    break;
                }
            }
        } catch (Exception\Max $e) {
            fclose($handle_input);
            fclose($handle_output);
            $this->response = new Response(null, 400);
        } catch (Exception\File $e) {
            fclose($handle_input);
            fclose($handle_output);
            $this->response = new Response(null, 500);
        } catch (Exception\Abort $e) {
            fclose($handle_input);
            fclose($handle_output);
            $this->response = new Response(null, 100);
        }

        $this->response = new Response(null, 200);
    }


    /**
     * Process the OPTIONS request
     *
     * @access  private
     */
    private function processOptions()
    {
        $this->response = new Response(null, 200);
    }


    /**
     * Process the GET request
     *
     * @access  private
     */
    private function processGet($send)
    {
        $file = $this->directory.$this->getFilename();

        if (file_exists($file) === false || is_readable($file) === false) {
            throw new Exception\Request('The file '.$this->uuid.' doesn\'t exist', 404);
        }

        $this->response = new Response(null, 200);
        $this->addCommonHeader();

        $this->response->headers->set('Content-Type', 'application/force-download', true);
        $this->response->headers->set('Content-disposition', 'attachment; filename="'.str_replace('"', '', basename($this->uuid)).'"', true);
        $this->response->headers->set('Content-Transfer-Encoding', 'application/octet-stream', true);
        $this->response->headers->set('Pragma', 'no-cache', true);
        $this->response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0, public', true);
        $this->response->headers->set('Expires', '0', true);

        if ($send === true) {
            $this->response->sendHeaders();

            readfile($file);
            exit;
        }
    }


    /**
     * Add the commons headers to the HTTP response
     *
     * @access  private
     */
    private function addCommonHeader()
    {
        $this->response->headers->set('Allow', 'OPTIONS,GET,HEAD,POST,PATCH', true);
        $this->response->headers->set('Access-Control-Allow-Methods', 'OPTIONS,GET,HEAD,POST,PATCH', true);
        $this->response->headers->set('Access-Control-Allow-Origin', '*', true);
        $this->response->headers->set('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Final-Length, Offset', true);
        $this->response->headers->set('Access-Control-Expose-Headers', 'Location, Range, Content-Disposition, Offset', true);
    }


    /**
     * Extract a list of headers in the HTTP headers
     *
     * @param   array       $headers        A list of header name to extract
     * @return  array                       A list if header ([header name => header value])
     * @throws  \InvalidArgumentException   If headers isn't array
     * @throws  \PhpTus\Exception\BadHeader If a header sought doesn't exist or are empty
     * @access  private
     */
    private function extractHeaders($headers)
    {
        if (is_array($headers) === false) {
            throw new \InvalidArgumentException('Headers must be an array');
        }

        $headers_values = array();
        foreach ($headers as $header) {
            $value = $this->getRequest()->headers->get($header);

            if (trim($value) === '') {
                throw new Exception\BadHeader($header.' can\'t be empty');
            }

            $headers_values[$header] = $value;
        }

        return $headers_values;
    }


    /**
     * Set the directory where the file will be store
     *
     * @param   string      $directory      The directory where the file are stored
     * @return  \PhpTus\Server              The current Server instance
     * @throws  \InvalidArgumentException   If directory isn't string
     * @throws  \PhpTus\Exception\File      If directory isn't writable
     * @access  private
     */
    private function setDirectory($directory)
    {
        if (is_string($directory) === false) {
            throw new \InvalidArgumentException('Directory must be a string');
        }
        
        if (is_dir($directory) === false || is_writable($directory) === false) {
            throw new Exception\File($directory.' doesn\'t exist or isn\'t writable');
        }

        $this->directory = $directory.(substr($directory, -1) !== DIRECTORY_SEPARATOR ? DIRECTORY_SEPARATOR : '');

        return $this;
    }


    /**
     * Set the path to use in the URI
     *
     * @param   string      $path           The path to use in the URI
     * @return  \PhpTus\Server              The current Server instance
     * @throws  \InvalidArgumentException   If path isn't string
     * @access  private
     */
    private function setPath($path)
    {
        if (is_string($path) === false) {
            throw new \InvalidArgumentException('Path must be a string');
        }

        $this->path = $path;

        return $this;
    }


    /**
     * Set the options to use for the Redis usage
     *
     * @param   null|array  $options        The options to use for the Redis usage
     * @return  \PhpTus\Server              The current Server instance
     * @throws  \InvalidArgumentException   If options is not null or array
     * @access  private
     */
    private function setRedisOptions($options)
    {
        if ($options === null) {
            return $this;
        }

        if (is_array($options) === true) {
            $this->redis_options = array_merge($this->redis_options, $options);
            return $this;
        }

        throw new \InvalidArgumentException('Options must be null or an array');
    }


    /**
     * Get the Redis connection
     *
     * @return  Predis\Client       The Predis client to use for manipulate Redis database
     * @access  private
     */
    private function getRedis()
    {
        if ($this->redis === null) {
            $this->redis = new PredisClient($this->redis_options);
        }

        return $this->redis;
    }


    /**
     * Set a value in the Redis database
     *
     * @param   string      $id     The id to use to set the value (an id can have multiple key)
     * @param   string      $key    The key for wich you want set the value
     * @param   mixed       $value  The value for the id-key to save
     * @access  private
     */
    private function setInRedis($id, $key, $value)
    {
        if (is_array($value) === true) {
            $this->getRedis()->hmset($this->redis_options['prefix'].$id, $key, $value);
        } else {
            $this->getRedis()->hset($this->redis_options['prefix'].$id, $key, $value);
        }
    }


    /**
     * Get a value in the Redis database
     *
     * @param   string      $id     The id to use to get the value (an id can have multiple key)
     * @param   string      $key    The key for wich you want value
     * @return  mixed               The value for the id-key
     * @access  private
     */
    private function getInRedis($id, $key)
    {
        return $this->getRedis()->hget($this->redis_options['prefix'].$id, $key);
    }


    /**
     * Check if an id exists in the Redis database
     *
     * @param   string      $id     The id to test
     * @return  bool                True if the id exists, false else
     * @access  private
     */
    private function existsInRedis($id)
    {
        return $this->getRedis()->exists($this->redis_options['prefix'].$id);
    }


    /**
     * Get the filename to use when save the uploaded file
     *
     * @return  string              The filename to use
     * @throws  \DomainException    If the path isn't define
     * @throws  \DomainException    If the uuid isn't define
     * @access  private
     */
    private function getFilename()
    {
        if ($this->path === null) {
            throw new \DomainException('Path can\'t be null when call '.__METHOD__);
        }

        if ($this->uuid === null) {
            throw new \DomainException('Uuid can\'t be null when call '.__METHOD__);
        }

        return str_replace($this->path, '', $this->uuid);
    }


    /**
     * Get the HTTP Request object
     *
     * @return  \Symfony\Component\HttpFoundation\Request       the HTTP Request object
     * @access  private
     */
    private function getRequest()
    {
        if ($this->request === null) {
            $this->request = Request::createFromGlobals();
        }

        return $this->request;
    }


    /**
     * Get the HTTP Response object
     *
     * @return  \Symfony\Component\HttpFoundation\Response      the HTTP Response object
     * @access  private
     */
    private function getResponse()
    {
        if ($this->response === null) {
            $this->response = new Response();
        }

        return $this->response;
    }
}
