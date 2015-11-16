<?php
namespace PhpMvc\Core;
class Authorization {
	private $_users;
	private $_roles;

	public function __construct($users = array(), $roles = array()) {
		$this->_users = $users;
		$this->_roles = $roles;
	}

	public function IsRoleAuthorized($roleName) {
		return in_array($roleName, $this->_roles);
	}

	public function IsUserAuthorized($userName) {
		return in_array($userName, $this->_users);
	}
}