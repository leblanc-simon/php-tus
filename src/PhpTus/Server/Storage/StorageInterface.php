<?php

namespace PhpTus\Storage;

interface StorageInterface
{
    public function __construct(array $options = []);

    /**
     * Set a value in the storage
     *
     * @param   string      $id     The id to use to set the value (an id can have multiple key)
     * @param   string      $key    The key for wich you want set the value
     * @param   mixed       $value  The value for the id-key to save
     * @access  public
     */
    public function set($id, $key, $value);

    /**
     * Get a value in the storage
     *
     * @param   string      $id     The id to use to get the value (an id can have multiple key)
     * @param   string      $key    The key for wich you want value
     * @return  mixed               The value for the id-key
     * @access  public
     */
    public function get($id, $key);

    /**
     * Check if an id exists in the storage
     *
     * @param   string      $id     The id to test
     * @return  bool                True if the id exists, false else
     * @access  public
     */
    public function exists($id);
}