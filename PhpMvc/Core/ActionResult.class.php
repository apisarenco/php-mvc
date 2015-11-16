<?php
namespace PhpMvc\Core;


class ActionResult {
	private $_output = "";

	public function __construct($content) {
		$this->_output = $content;
	}

	public function Output() {
		echo $this->_output;
	}
}