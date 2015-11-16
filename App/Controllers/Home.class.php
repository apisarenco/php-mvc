<?php
namespace App\Controllers;

use App\Models\Home\IndexVM;
use PhpMvc\Core\Application;

class Home extends \PhpMvc\Core\Controller {
	public function __construct() {
		parent::__construct();
		$this->viewBag->Title = "PhpMVC - Home";
	}

	public function GetIndex() {
		$model = new IndexVM();
		$model->name = Application::$Session->name;
		return $this->View($model);
	}

	public function PostIndex(IndexVM $model) {
		Application::$Session->name = $model->name;
		Application::Redirect('~/Home/Index');
	}
}