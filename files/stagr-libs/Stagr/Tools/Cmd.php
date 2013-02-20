<?php

/*
 * This file is part of the Stagr framework.
 *
 * (c) Gabriel Manricks <gmanricks@me.com>
 * (c) Ulrich Kautz <ulrich.kautz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stagr\Tools;

/**
 * Second Cmd implementation running commands with verbosity option
 *
 * @author Gabriel Manricks <gmanricks@me.com>
 * @author Ulrich Kautz <ulrich.kautz@gmail.com>
 */

class Cmd
{
    /**
     * Run command, possibly prints debug output if DEBUG env variable is set. Does nothing if DRYRUN env varabiale is set.
     *
     * @param string $cmd The command
     *
     * @return string
     */
    public static function run($cmd)
    {
        if (isset($_SERVER['DEBUG']) && $_SERVER['DEBUG'] > 0) {
            echo "DEBUG::run: \"$cmd\"\n";
        }
        if (isset($_SERVER['DRYRUN']) && $_SERVER['DRYRUN'] > 0) {
            return "DRYRUN\n";
        }
        return exec($cmd);
    }

    /**
     * Run command and return whether successfull or not
     *
     * @param string $cmd The command
     *
     * @return bool
     */
    public static function runCheck($cmd)
    {
        $res = self::run(sprintf('((%s) && echo OK || echo FAIL) | tail -1', $cmd));
        return isset($_SERVER['DRYRUN']) && $_SERVER['DRYRUN'] > 0 ? true : strpos($res, 'FAIL') === false;
    }

    /**
     * Run command and detaches current process.
     *
     * @param string $path Path to executable
     * @param array  $args Command line args (optional)
     * @param array  $env  Env for execution (optional)
     */
    public static function runDetach($path, array $args = array(), array $env = array())
    {
        if (isset($_SERVER['DEBUG']) && $_SERVER['DEBUG'] > 0) {
            $cmd = $path. ' '. join(' ', $args);
            echo "DEBUG::run: \"$cmd\"\n";
        }
        if (isset($_SERVER['DRYRUN']) && $_SERVER['DRYRUN'] > 0) {
            exit;
        }
        call_user_func_array('pcntl_exec', [$path, $args, $env]);
    }

}
