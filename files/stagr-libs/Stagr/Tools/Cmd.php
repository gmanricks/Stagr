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
     * Run command, possibly debug output
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
        return strpos($res, 'FAIL') === false;
    }

}
