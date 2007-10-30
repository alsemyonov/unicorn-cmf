<?php

class Unicorn {
	
}

class Registry implements ArrayAccess {
	private $vars = array();

	function __construct() {
		
	}
	
	function getInstance() {
		static $instance = array();

		if (empty($instance)) {
			$instance[0] = new Registry();
		}

		return $instance[0];
	}
	
	function set($key, $var) {
		$_this = Registry::getInstance();

		if (isset($_this->vars[$key]) == true) {
			throw new Exception('Unable to set var `' . $key . '`. Already set.');
		}

		$_this->vars[$key] = $var;
		return true;
	}

	function get($key) {
		$_this = Registry::getInstance();

		if (isset($_this->vars[$key]) == false) {
			return null;
		}

		return $_this->vars[$key];
	}

	function remove($var) {
		$_this = Registry::getInstance();

		unset($_this->vars[$key]);
	}
}

function __autoload($className) {
	
}

