<?php

namespace PhpTus\Storage;

use Predis\Client;

class Redis implements StorageInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $options  = [
        'prefix'    => 'php-tus-',
        'scheme'    => 'tcp',
        'host'      => '127.0.0.1',
        'port'      => '6379',
    ];


    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }


    /**
     * Set the options to use for the Redis usage
     *
     * @param   array       $options        The options to use for the Redis usage
     * @return  self                        The current Redis instance
     * @throws  \InvalidArgumentException   If options is not null or array
     * @access  private
     */
    private function setOptions($options)
    {
        if (is_array($options) === false) {
            throw new \InvalidArgumentException('Options must be an array');
        }

        $this->options = array_merge($this->options, $options);
        return $this;
    }


    /**
     * Get the Redis connection
     *
     * @return  Client       The Predis client to use for manipulate Redis database
     * @access  private
     */
    private function getClient()
    {
        if ($this->client === null) {
            $this->client = new Client($this->options);
        }

        return $this->client;
    }


    /**
     * Set a value in the Redis database
     *
     * @param   string      $id     The id to use to set the value (an id can have multiple key)
     * @param   string      $key    The key for wich you want set the value
     * @param   mixed       $value  The value for the id-key to save
     * @access  public
     */
    public function set($id, $key, $value)
    {
        if (is_array($value) === true) {
            $this->getClient()->hmset($this->options['prefix'].$id, $key, $value);
        } else {
            $this->getClient()->hset($this->options['prefix'].$id, $key, $value);
        }
    }


    /**
     * Get a value in the Redis database
     *
     * @param   string      $id     The id to use to get the value (an id can have multiple key)
     * @param   string      $key    The key for wich you want value
     * @return  mixed               The value for the id-key
     * @access  public
     */
    public function get($id, $key)
    {
        return $this->getClient()->hget($this->options['prefix'].$id, $key);
    }


    /**
     * Check if an id exists in the Redis database
     *
     * @param   string      $id     The id to test
     * @return  bool                True if the id exists, false else
     * @access  public
     */
    public function exists($id)
    {
        return $this->getClient()->exists($this->options['prefix'].$id);
    }
}