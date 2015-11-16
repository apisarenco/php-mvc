<?php
namespace PhpMvc\Core;

class Controller {
	/**
	 * @param $controllerAction ControllerActionInfo
	 * @param $params array
	 * @return ActionResult
	 */
	public static function Execute(ControllerActionInfo $controllerAction, $params) {
		return $controllerAction->Execute($params);
	}

	public function __construct() {
		$this->viewBag = new ViewBag();
	}

	protected $viewBag;
	protected function View($viewModel=null) {
		$trace=debug_backtrace();
		$caller=$trace[1];

		\PhpMvc\View::Setup();
		\PhpMvc\View::SetViewBag($this->viewBag);
		\PhpMvc\View::RenderForController($viewModel, Application::$Controller, Application::$Action, Application::$Method);

		return new ViewResult(\PhpMvc\View::GetContent());
	}

	public function GetAuthorization($actionMethodName) {
		return null;
	}
}