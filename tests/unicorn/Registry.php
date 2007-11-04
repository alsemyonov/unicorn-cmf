<?php

require 'PHPUnit/Framework.php';
require '../unicorn/core.php';

class Unicorn_Registry extends PHPUnit_Framework_TestCase
{
	public function setUp() {
		$this->reg = ucRegistry::getInstance();
	}

	public function testSingle() {
		$reg  = ucRegistry::getInstance();
		
		$this->assertEquals($reg, $this->reg);
	}

	public function testSetOnlyOnce() {
		$this->reg->a = 'a';
		$this->assertEquals('a', $this->reg->a);
		
		$this->reg->a = 'b';
		$this->assertEquals('a', $this->reg->a);
	}

	public function testSetRemoveAndSet() {
		
		$this->reg->b = 'a';
		$this->assertEquals('a', $this->reg->b);
		
		unset($this->reg->b);
		$this->reg->b = 'b';
		$this->assertEquals('b', $this->reg->b);
	}

	public function testSetRemoveAndSetwithArray() {
		$this->reg->c = 'a';
		$this->assertEquals('a', $this->reg['c']);
		
		unset($this->reg->c);
		$this->reg['c'] = 'b';
		$this->assertEquals('b', $this->reg->c);
	}
}
?>