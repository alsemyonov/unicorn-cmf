<?php

require_once 'PHPUnit/Framework.php';
require_once '../unicorn/core.php';

class ucConfigureTest extends PHPUnit_Framework_TestCase
{
	public function setUp() {
		$this->conf = ucConfigure::getInstance();
	}

	public function testSingle() {
		$conf  = ucConfigure::getInstance();
		
		$this->assertEquals($conf, $this->conf);
	}

	public function testMaySetTwice() {
		$this->conf->a = 'a';
		$this->assertEquals('a', $this->conf->a);
		
		$this->conf->a = 'b';
		$this->assertEquals('b', $this->conf->a);
	}
}
?>