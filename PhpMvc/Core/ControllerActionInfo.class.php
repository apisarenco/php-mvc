<?php
namespace PhpMvc\Core;
class ControllerActionInfo {
	private $_controller;
	private $_actionMethodName;

	public function __construct(Controller $controller, $actionMethodName) {
		$this->_controller = $controller;
		$this->_actionMethodName = $actionMethodName;
	}

	public function IsAuthorized($userName=null, $roleName=null) {
		$authorization = $this->_controller->GetAuthorization($this->_actionMethodName);
		if($authorization==null) {
			//all are allowed
			return true;
		}
		elseif($authorization instanceof Authorization) {
			return $authorization->IsUserAuthorized($userName) || $authorization->IsRoleAuthorized($roleName);
		}
		throw new \Exception("Invalid authorization");
	}

	public function GetMethodInfo() {
		return new \ReflectionMethod($this->_controller, $this->_actionMethodName);
	}

	public function Execute($params) {
		return call_user_func_array(array($this->_controller, $this->_actionMethodName), $params);
	}
}