<?php
namespace PhpMvc\Core;

class Utility {
	public static function GetPath($rootRelativePath) {
		return realpath(__DIR__.'/../../'.$rootRelativePath);
	}
}