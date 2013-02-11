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
 * Setup logic for creating applications
 *
 * @author Gabriel Manricks <gmanricks@me.com>
 * @author Ulrich Kautz <ulrich.kautz@gmail.com>
 */

class Setup
{
    /**
     * @const string Home folder of stagr/vagrant user
     */
    const STAGR_HOME_DIR = '/home/vagrant';

    /**
     * Prints Stagr Log to STDOUT
     *
     * @param  string  $action  Output action title, eg Setup
     */
    public static function printLogo($action = 'Setup')
    {
        echo <<<LOGO

[1m
       ___ _
      / __| |_ __ _ __ _ _ _
      \__ \  _/ _` / _` | '_|
      |___/\__\__,_\__, |_|
                   |___/
[0m
     [31mStaging Enviroment[0m $action


LOGO;
    }
}
