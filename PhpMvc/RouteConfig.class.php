<?php
namespace PhpMvc;

class RouteConfig {
	public static function Configure(Core\RouteCollection $routes) {
		$routes->Add("/{controller}/{action}/",
			array('controller'=>'Home', 'action'=>'Index'),
			array('controller'=>'/\w+/', 'action'=>'/\w+/'));
	}
}