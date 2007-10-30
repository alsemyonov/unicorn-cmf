<?php

class Behaviour {
	
}

class Datasource {
	
}

abstract class Model implements ArrayObject {
	public $name;
	public $data = array	();
	private $schemeInfo = array();

	function __construct($array = null) {
		if (null == $name) {
			$name = get_class($this);
		}
	}

	public function set($data = null) {
		if (is_array($data)) {
			if (!isset($data[$this->name])) {
				$data = array($this->name => $data);
			}
			
			
		}
	}

	public function create($data = null) {
		$this->set($data);

	}

	public function read($fields = null) {

	}

	public function update($data = array()) {
		
	}

	public function delete($id = null) {
		
	}
}