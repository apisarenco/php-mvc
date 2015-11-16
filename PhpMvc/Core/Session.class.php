<?php
namespace PhpMvc\Core;


class Session {
	private $_data;

	public function __get($key) {
		return $this->_data[$key];
	}

	public function __set($key, $value) {
		return $this->_data[$key]=$value;
	}

	public function __isset($key) {
		return isset($this->_data[$key]);
	}

	public function __unset($key) {
		unset($this->_data[$key]);
	}
}