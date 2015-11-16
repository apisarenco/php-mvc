<?php use PhpMvc\Core\Application; use PhpMvc\View; ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<title><?= $model->Title; ?></title>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta content="width=device-width, initial-scale=1" name="viewport"/>

		<link rel="stylesheet" href="<?=Application::GetUrl('~/Content/css/bootstrap.css'); ?>"/>
		<link rel="stylesheet" href="<?=Application::GetUrl('~/Content/css/bootstrap-theme.css'); ?>"/>

		<?php View::Section("head"); ?>

	</head>

<body>
	<div class="navbar navbar-inverse navbar-static-top">
		<div class="navbar-left">
			<a href="#" class="navbar-brand"><span class="glyphicon glyphicon-globe"></span></a>
		</div>
	</div>

	<?php View::Page(); ?>

	<script type="text/javascript" src="<?=Application::GetUrl('~/Content/js/boostrap.min.js'); ?>"></script>
	<?php View::Section("tail"); ?>
</body>
</html>