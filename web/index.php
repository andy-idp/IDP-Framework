<?php

use Core\Application;
use Symfony\Component\HttpFoundation\Request;

require_once (__DIR__ . "/../vendor/autoload.php");
require_once (__DIR__ . "/../Core/errors.php");

$request = Request::createFromGlobals();

$application = new Application($request);
$application->launch();