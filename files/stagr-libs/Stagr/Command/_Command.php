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

namespace Stagr\Command;

use Symfony\Component\Console\Command\Command;
#use Cilex\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Example command for testing purposes.
 */
abstract class _Command extends Command
{
    /**
     * Read from STDIN, print out prefix
     *
     * @param  OutputInterface  $output      Output class
     * @param  string           $prefix      Prefix for input presentation [opt]
     * @param  mixed            $matchCheck  Either regex or closure to check whether input is allowed [opt]
     * @param  bool             $inverse     Whether regex resupt should be inverted [opt]
     * @param  string           $errorOut    Output on input error [opt]
     */
    public function readStdin(OutputInterface &$output, $prefix = '> ', $matchCheck = null, $inverse = false, $errorOut = null)
    {
        $res = '';
        $match = !$matchCheck
            ? function ($in) {
                return true;
            }
            : (is_callable($matchCheck) && is_object($matchCheck)
                ? $matchCheck
                : function ($in) use ($matchCheck) {
                    return preg_match($matchCheck, $in);
                });
        while (true) {
            $output->write($prefix);
            $res = readline();
            if ($match($res)) {
                return $res;
            }
            if ($errorOut) {
                $output->writeln("<error>** $errorOut **</error>");
            }
        }
    }


}