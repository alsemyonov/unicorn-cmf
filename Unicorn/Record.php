<?php

/**
 * –еализаци€ ORM дл€ Unicorn, основанна€ на интерфейсе DbSimple 
 *
 * @category   Unicorn
 * @package    Unicorn_Record
 * @copyright  Copyright (c) 2007 roTuKa
 * @license    New BSD License
 */
class Unicorn_Record {
    static $_name = null;
    protected $_tableName;
    
    static function find($conditions = null) {
    
    }
    
    /**
     * —оздаЄт новую запись, использу€ данные, переданные ей в качестве первого параметра
     * with the current Zend_Version::VERSION of the Zend Framework.
     *
     * @param  string  $version  A version string (e.g. "0.7.1").
     * @param  string  $version  A version string (e.g. "0.7.1").
     * @return Unicorn_ActiveRecord
     */
    static function create(array $data = array()) {
        $name = self::$_name;
        return new $name($data);
    }
    
    public function __construct(array $data = array()) {
    
    }
    
    public function retrieve() {
    
    }
    
    public function update(array $data = array()) {
    
    }
    
    public function delete($conditions = null) {
    
    }
}