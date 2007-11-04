<?php
    
class ucRegistry implements ArrayAccess {
	private $vars = array();

	static function getInstance() {
		static $instance = array();

		if (empty($instance)) {
			$instance[0] = new ucRegistry();
		}

		return $instance[0];
	}
	
	function offsetExists($offset) {
		return isset($this->vars[$offset]);
	}

	function offsetGet($key) {
		return $this->__get($key);
	}
	
	function offsetSet($key, $value) {
		return $this->__set($key, $value);
	}

	function offsetUnset($key) {
		$this->__unset($key);
	}

	function __isset($key) {
		return isset($this->vars[$key]);
	}

	function __unset($key) {
		unset($this->vars[$key]);
	}

	function __get($key) {
		if (!isset($this->vars[$key])) {
			return null;
		}
		return $this->vars[$key];
	}
	
	function __set($key, $value) {
		if (!isset($this->vars[$key])) {
			$this->vars[$key] = $value;
		}
	}
}


