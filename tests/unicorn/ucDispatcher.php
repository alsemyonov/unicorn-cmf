<?php

require_once 'PHPUnit/Framework.php';
require_once '../unicorn/core.php';

class ucDispatcherTest extends PHPUnit_Framework_TestCase
{
  public function setUp() {
    
  }
  
  public function testOverriding() {
    try {
      $disp = new ucDispatcher();
    } catch (Exception $e) {
      $this->assertEquals("Please, implement method ucDispatcher::dispatch();", $e->getMessage());
    }
  }
  
  public function testParamsParsing() {
    $params = array('console', '-app', './app/', '-core', './../core/');
    $mustBe = array(
      'pass' => array(
        'console'
      ),
      'config' => array(
        'app' => './app/',
        'core' => './../core/',
        'vendors' => './vendors/',
        'tests' => './tests/',
        'docs' => './docs/'
      ),
    );
    $disp = new ucShellDispatcher($params);
    $params = $disp->getParams();
    $this->assertEquals($mustBe, $params);
  }
}
?>