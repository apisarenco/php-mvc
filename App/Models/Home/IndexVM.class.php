<?php
namespace App\Models\Home;


class IndexVM
{
	public $name;

	public function getName() {
		if(empty($this->name)) {
			return "World";
		}
		return $this->name;
	}
}