<?php

require_once 'PHPUnit/Util/Filter.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__);

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Unicorn_AllTests::main');
    chdir(dirname(__FILE__));
}

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once 'PHPUnit/Util/Filter.php';

require_once 'unicorn/ucCore.php';

class Unicorn_AllTests {
    public static function main() {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite('Unicorn');

        $suite->addTestSuite(ucCoreTests::suite());

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Unicorn_AllTests::main') {
    Unicorn_AllTests::main();
}
