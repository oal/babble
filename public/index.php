<?php

require_once '../vendor/autoload.php';

use Babble\Babble;

if (preg_match('/static/', $_SERVER["REQUEST_URI"])) {
    return false;
}

new Babble();