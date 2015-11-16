<?php
namespace PhpMvc;

class View {
	private static $_content;
	private static $_mainSection;
	private static $_sectionContent = array();
	private static $_modelStack = array();
	private static $_viewDirectory;

	public static function GetContent() {
		return self::$_content;
	}

	public static function Setup() {
		self::$_viewDirectory = Core\Utility::GetPath('App/Views/').'/';
	}

	private static $_viewBag;
	public static function SetViewBag($viewBag) {
		self::$_viewBag = $viewBag;
	}

	public static function RenderForController($viewModel, $class, $function, $method = 'Get') {
		$classArray = explode('\\', $class);
		$class = array_pop($classArray);
		$files = [
			self::$_viewDirectory.$class.'/'.$method.$function.'.php',
			self::$_viewDirectory.$class.'/'.$function.'.php',
			self::$_viewDirectory.'_shared/'.$method.$function.'.php',
			self::$_viewDirectory.'_shared/'.$function.'.php'
		];
		while(!file_exists($view = array_shift($files))) {
			if(empty($files)) {
				throw new \Exception("View not found for $class/$function and method $method");
			}
		}

		self::Render($viewModel, $view);

		self::RenderLayout();
	}

	public static function Render($viewModel, $view) {
		global $model;
		$model = $viewModel;
		array_push(self::$_modelStack, $model);
		ob_start();
		include($view);
		self::$_content.=ob_get_contents();
		ob_end_clean();
		$model = array_pop(self::$_modelStack);
	}

	private static function Interrupt() {
		self::$_content.=ob_get_contents();
		ob_end_clean();
		ob_start();
	}

	private static $_currentSection;
	public static function StartSection($sectionName) {
		if(self::$_currentSection) {
			throw new \Exception(self::$_currentSection.' section is not ended');
		}
		self::$_currentSection = $sectionName;
		self::Interrupt();
		self::$_mainSection = self::$_content;
		self::$_content = "";
	}

	public static function EndSection() {
		if(!self::$_currentSection) {
			throw new \Exception('No section is not started');
		}
		self::Interrupt();
		self::$_sectionContent[self::$_currentSection] = self::$_content;
		self::$_content = self::$_mainSection;
		self::$_currentSection = null;
	}

	private static $_layoutFile;
	public static function Layout($layoutFile) {
		self::$_layoutFile = self::$_viewDirectory.$layoutFile;
	}

	public static function RenderLayout() {
		if(!self::$_layoutFile) {
			throw new \Exception("No Layout file");
		}
		self::$_mainSection = self::$_content;
		self::$_content = "";
		self::Render(self::$_viewBag, self::$_layoutFile);
	}

	public static function Section($sectionName) {
		if(isset(self::$_sectionContent[$sectionName])) {
			echo self::$_sectionContent[$sectionName];
		}
	}

	public static function Page() {
		echo self::$_mainSection;
	}
}