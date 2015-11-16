<?php
namespace PhpMvc\Core;

class Application {
	public static $Controller;
	public static $Action;
	public static $Method;
	public static $Session = null;
	public static $BasePath;

	public function __construct() {
		self::$BasePath = substr(dirname($_SERVER['SCRIPT_FILENAME']), strlen($_SERVER['DOCUMENT_ROOT']));

		if(isset($_SESSION['serialized_session'])) {
			try {
				self::$Session = unserialize($_SESSION['serialized_session']);
			}
			catch(\Exception $ex) {

			}
		}
		if(!self::$Session) {
			self::$Session = new Session();
		}
	}

	public function __destruct() {
		$_SESSION['serialized_session'] = serialize(self::$Session);
	}

	public function Run() {
		$url = $_SERVER['REQUEST_URI'];
		if(strlen(self::$BasePath)>strlen($url)) {
			throw new \Exception("Requested path canot be resolved to this application.");
		}
		$baseUrl = substr($url, 0, strlen(self::$BasePath));
		if(strtolower($baseUrl)!=strtolower(self::$BasePath)) {
			throw new \Exception("Document root does not match current path.");
		}
		$url = substr($url, strlen(self::$BasePath));

		self::$Method = ucfirst(strtolower($_SERVER['REQUEST_METHOD']));
		$urlParts = explode('/',$url);
		array_shift($urlParts);
		if(!self::$Controller=array_shift($urlParts)) {
			self::$Controller = 'Home';
		}
		if(!self::$Action=array_shift($urlParts)) {
			self::$Action = 'Index';
		}
		$controllerAction = $this->GetControllerAction();


		if($this->IsAuthenticated()) {
			$currentUser = self::$Session->user;
			if (!$controllerAction->IsAuthorized($currentUser)) {
				self::Redirect('~/Error/Code400', 400);
			}
		}
		elseif(!$controllerAction->IsAuthorized()) {
			self::Redirect("~/Account/Login", 307);
		}


		$arguments = $this->GetParametersFor($controllerAction, $urlParts);

		$actionResult = Controller::Execute($controllerAction, $arguments);
		$actionResult->Output();
	}

	private function GetControllerAction() {
		$controllerClass = 'App\Controllers\\'.self::$Controller;
		if(!class_exists($controllerClass)) {
			self::$Controller = 'Error';
			self::$Action = 'Code404';
			return $this->GetControllerAction();
		}
		$methodName = self::$Method.self::$Action;
		if(!method_exists($controllerClass, $methodName)) {
			$methodName = self::$Action;
			if(!method_exists($controllerClass, $methodName)) {
				self::$Controller = 'Error';
				self::$Action = 'Code404';
				return $this->GetControllerAction();
			}
		}

		$controller = new $controllerClass();
		if(!$controller instanceof Controller) {
			self::$Controller = 'Error';
			self::$Action = 'Code404';
			return $this->GetControllerAction();
		}

		return new ControllerActionInfo($controller, $methodName);
	}

	private function GetParametersFor(ControllerActionInfo $controllerActionInfo, $urlParts) {
		$methodInfo = $controllerActionInfo->GetMethodInfo();

		$params = $methodInfo->getParameters();
		$arguments = [];
		foreach ($params as $param) {
			$name = $param->getName();
			$optional = $param->isOptional();
			$type = $param->getClass();
			if($type!=null) {
				//build from POST data
				$typeName = $type->name;
				$vm = new $typeName();
				$classInfo = new \ReflectionClass($typeName);
				$properties = $classInfo->getProperties();
				foreach ($properties as $property) {
					$propertyName = ltrim($property->getName(), '_');

					if (isset($_POST[$propertyName])) {
						$property->setValue($vm, $_POST[$propertyName]);
					}
				}
				$arguments[] = $vm;
			}
			elseif(isset($_POST[$name])) {
				$arguments[] = $_POST[$name];
			}
			elseif(!empty($urlParts)) {
				$arguments[] = array_shift($urlParts);
			}
			elseif(!$optional) {
				throw new \Exception(sprintf("Can't provide argument for action method %s, for parameter %s", $methodInfo->getName(), $name));
			}
		}

		return $arguments;
	}

	private function IsAuthenticated() {
		return isset(self::$Session->user);
	}

	public static function Redirect($location, $status=302) {
		$statuses = array(
			301=>'Moved Permanently',
			302=>'Found',
			303=>'See Other',
			304=>'Not Modified',
			305=>'Use Proxy',
			307=>'Temporary Redirect',
		);
		header("HTTP/1.0 $status {$statuses[$status]}");
		header("Location: ".self::GetUrl($location));
		exit();
	}

	public static function GetUrl($url) {
		return str_replace('~', self::$BasePath, $url);
	}
}