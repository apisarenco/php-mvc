<?php
namespace PhpMvc\Core;

class ViewResult extends ActionResult{
	public function __construct($content) {
		parent::__construct($content);
	}
}