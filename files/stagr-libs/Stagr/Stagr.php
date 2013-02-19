<?php


/*
 * This file is part of Stagr.
 *
 */

namespace Stagr;

use Stagr\Tools\Setup;

/**
 * Class Description [TODO]
 *
 * @author Gabriel Manricks <gmanricks@icloud.com>
 * @author Ulrich Kautz <ulrich.kautz@gmail.com>
 */

class Stagr extends \Cilex\Application
{
    const VERSION = '0.1.1';

    /**
     * @var Array
     */
    private $config = ['email' => null, 'sshkeys' => []];



    /**
     * Constructor for Stagr\Stagr
     *
     * @param \PHook  $ph  PHpook instance
     */
    public function __construct()
    {
        parent::__construct('Stagr', self::VERSION);
        $this->initConfig();
        $this->command(new \Cilex\Command\GreetCommand());
        foreach (glob(__DIR__. '/Command/*Command.php') as $file) {
            $cmdClass = preg_replace('/^.+\/(.+Command)\.php$/', '$1', $file);
            if (strpos($cmdClass, '_') === 0) {
                continue;
            }
            $cmdClass = '\\Stagr\\Command\\'. $cmdClass;
            $this->command(new $cmdClass());
        }
    }

    /**
      * Read/write config parameter
      *
      * @param  string  $name   Config key name to be read/written
      * @param  mixed   $value  Config value to be written [option]
      *
      * @return mixed   The config param value or null
      */
    public function configParam($name, $value = null)
    {
        if (!is_null($value)) {
            $this->config[$name] = $value;
            file_put_contents(Setup::STAGR_HOME_DIR. '/.stagr', yaml_emit($this->config)); // yaml_emit_file not yet implemented
        }
        return isset($this->config[$name])
            ? $this->config[$name]
            : null;
    }

    /**
      * Unset a parameter completely
      *
      * @param  string  $name   Config key name to be unset
      *
      * @return boolean   True if deleted or false if didn't exist
      */
    public function unsetParam($name)
    {
        if (isset($this->config[$name])) {
            unset($this->config[$name]);
            return true;
        }
        return false;
    }

    /**
      * Reads stagr YAML config
      */
    private function initConfig()
    {
        $configFile = Setup::STAGR_HOME_DIR. '/.stagr';
        if (file_exists($configFile)) {
            $this->config = yaml_parse_file($configFile);
        } else {
            $this->config = [];
        }
    }

}
