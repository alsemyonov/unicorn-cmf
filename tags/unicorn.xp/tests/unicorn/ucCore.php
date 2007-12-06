<?php

require_once 'PHPUnit/Util/Filter.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__);

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'ucCoreTests::main');
    chdir(dirname(dirname(__FILE__)));
}

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once 'PHPUnit/Util/Filter.php';

require_once 'unicorn/ucRegistry.php';
require_once 'unicorn/ucConfigure.php';
require_once 'unicorn/ucDispatcher.php';

class ucCoreTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Unicorn_Core');

        $suite->addTestSuite('ucRegistryTest');
        $suite->addTestSuite('ucConfigureTest');
        $suite->addTestSuite('ucDispatcherTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'ucCoreTests::main') {
    ucCoreTests::main();
}
