<?php
namespace PhpMvc\Core;

class ViewBag {
	private $_data = array();
	public function __get($key) {
		if(isset($this->_data[$key]))
			return $this->_data[$key];
		return null;
	}

	public function __set($key, $value) {
		$this->_data[$key] = $value;
	}
}