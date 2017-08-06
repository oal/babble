<?php

require_once '../vendor/autoload.php';

use Babble\Babble;

if (preg_match('/^(\/(static|admin|uploads)(\/|$))/', $_SERVER['REQUEST_URI'])) {
    return false;
}

$babble = new Babble();
$babble->serve();
