<?php



class ucRegistry implements ArrayAccess {
	protected $vars = array();

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


class ucConfigure extends ucRegistry {
	
	static function getInstance() {
		static $instance = array();

		if (empty($instance)) {
			$instance[0] = new ucConfigure();
		}

		return $instance[0];
	}

	function __set($key, $value) {
		$this->vars[$key] = $value;
	}
	
}

class ucDispatcher {
	protected $_params = array(
		'pass' => array(),
		'config' => array(
			'app' => './application/',
			'core' => './unicorn/',
			'vendors' => './vendors/',
			'tests' => './tests/',
			'docs' => './docs/'
		),
	);

	function ucDispatcher($params = array()) {
		$this->__construct($params);
	}

	function __construct($params = array()) {
		$this->parseParams($params);
		$this->__initConsts();
		$this->__initEnviroments();
		$this->dispatch();
	}

	function parseParams($aParams = array()) {
		$aOut = array();
		$iParamsCount = count($aParams);
		for($i = 0; $i < $iParamsCount; $i++) {
			if (strpos($aParams[$i], '-') === 0) {
				$this->_params['config'][substr($aParams[$i], 1)] = str_replace('"', '', $aParams[++$i]);
			} else {
				$this->_params['pass'][] = $aParams[$i];
			}
		}
	}

	function __initConsts() {
		if (function_exists('ini_set')) {
			ini_set('display_errors', '1');
			ini_set('error_reporting', E_ALL);
			ini_set('html_errors', false);
			ini_set('implicit_flush', true);
			ini_set('max_execution_time', 60 * 5);
		}
		define('PHP5', (phpversion() >= 5));
		define('DS', DIRECTORY_SEPARATOR);
	}

	function __initEnviroments() {
		$this->stdin = fopen('php://stdin', 'r');
		$this->stdout = fopen('php://stdout', 'w');
		$this->stderr = fopen('php://stderr', 'w');
		
	}

	function dispatch() {
		throw new Exception("Please, implement method " . get_class($this) . "::dispatch();");
	}
	
	function getParams() {
		return $this->_params;
	}
	
	function e($message = null, $newline = true) {
		echo $message . ($newline ? "\n" : null);
	}
	
	function ee() {
		
	}
}

class ucShellDispatcher extends ucDispatcher {
	function dispatch() {
	}
}