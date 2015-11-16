<?php
namespace App\Controllers;

use PhpMvc\Core\Controller;

class Error extends Controller{
	public function Code404() {
		$this->viewBag->Title = "404 Error";
		return $this->View();
	}
}