<?php


/*
 * This file is part of Stagr.
 *
 */

namespace Stagr;

/**
 * Class Description [TODO]
 *
 * @author Gabriel Manricks <gmanricks@icloud.com>
 * @author Ulrich Kautz <ulrich.kautz@gmail.com>
 */

class Stagr
{

    /**
     * @var \PHook
     */
    private $pHook;


    /**
     * Constructor for Stagr\Stagr
     *
     * @param \PHook  $ph  PHpook instance
     */
    public function __construct(\PHook $ph)
    {
        $this->pHook = $ph;
    }

    /**
     * Test
     */
    public function run()
    {
        $this->printLogo();
        if (posix_geteuid() !== 0) {
            echo $this->pHook->red("Error: ")->plain("this script must be run as ")->red("root")->plain("\n")->withoutACommand();
            exit(1);
        }


    }


    /**
     * Prints the Stagr logo
     */
    private function printLogo()
    {
        echo <<<LOGO

[1m
       ___ _
      / __| |_ __ _ __ _ _ _
      \__ \  _/ _` / _` | '_|
      |___/\__\__,_\__, |_|
                   |___/
[0m
     [31mStaging Enviroment[0m Setup


LOGO;
    }


}
