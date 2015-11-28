<?php

namespace PhpTus\Server\Extension;

class Browser
{
    /**
     * @var array the list of directory which contain the extensions
     */
    private $directories = [];

    /**
     * @var DefinitionInterface[] the list of extensions loaded
     */
    private $extensions = [];

    /**
     * Browser constructor.
     * @param array $directories the list of directory which contain the extensions
     */
    public function __construct($directories = [])
    {
        $this->directories[] = __DIR__;

        $this->directories = array_unique(array_merge($this->directories, $directories));
    }

    /**
     * Load the available extensions
     * @return $this
     */
    public function load()
    {
        $this->extensions = [];

        foreach ($this->directories as $directory) {
            $extension_directories = glob($directory.'/*', GLOB_ONLYDIR);
            foreach ($extension_directories as $extension_directory) {
                if (
                    is_file($extension_directory.'/Definition.php') === false
                    ||
                    is_readable($extension_directory.'/Definition.php') === false
                ) {
                    continue;
                }

                $class = $this->getClassName($extension_directory.'/Definition.php');
                $definition = new $class();

                if ($definition instanceof DefinitionInterface) {
                    $this->extensions[$definition->getName()] = $definition;
                }
            }
        }

        return $this;
    }

    /**
     * Get the list of extension's name loaded
     * @return array
     */
    public function getExtensionNames()
    {
        return array_keys($this->extensions);
    }

    /**
     * Return the list of extension processors
     * @return ExtensionInterface[]
     */
    public function getExtensionProcessors()
    {
        $processors = [];
        foreach ($this->extensions as $extension) {
            $processors = array_merge($processors, $extension->getClasses());
        }

        return $processors;
    }

    /**
     * Get the full qualified class name contained in file
     *
     * @param string $filename the file to parse
     * @return string the full qualified class name
     * @throws \RuntimeException if class name is not found in the file
     */
    private function getClassName($filename)
    {
        $content = file_get_contents($filename);
        if (false === $content) {
            throw new \RuntimeException('Impossible to read extension\'s definition file');
        }

        if (preg_match('/^\s*namespace\s+([a-zA-Z0-9\\\\_]+)\s*;\s*$/m', $content, $match) === 1) {
            $namespace = $match[1].'\\';
        } else {
            var_dump($match);
            $namespace = '';
        }

        if (preg_match('/^\s*class\s+([a-zA-Z0-9_]+)\s*/m', $content, $match) === 0) {
            throw new \RuntimeException(sprintf('No classname found in file "%s"', $filename));
        }

        return $namespace.$match[1];
    }
}