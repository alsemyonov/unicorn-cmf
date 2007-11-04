<?php

require 'PHPUnit/Framework.php';
require '../unicorn/core.php';

class Unicorn_Configure extends PHPUnit_Framework_TestCase
{
	public function setUp() {
		$this->reg = ucConfigure::getInstance();
	}

	public function testSingle() {
		$reg  = ucConfigure::getInstance();
		
		$this->assertEquals($reg, $this->reg);
	}
}
?>