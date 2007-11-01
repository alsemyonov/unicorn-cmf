<?php

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__) . DS);
define('UNICORN', ROOT . 'unicorn' . DS);
define('APP', ROOT . 'application' . DS);

include UNICORN . 'bootstrap.php';
include APP . 'bootstrap.php';