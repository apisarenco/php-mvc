<?php use PhpMvc\Core\Application; use PhpMvc\View; View::Layout("__layout.php");?>

<div class="container">
	<div class="jumbotron text-center">
		<h1>Hello <?=$model->getName(); ?>!</h1>
		<p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.</p>

		<form action="<?= Application::GetUrl('~/Home/Index'); ?>" method="post">
			<div class="input-group">
				<input type="text" class="form-control" name="name" placeholder="My name is...">
				<span class="input-group-btn">
					<button class="btn btn-default" type="submit">Hello!</button>
				</span>
			</div><!-- /input-group -->
		</form>
	</div>
</div>

<?php View::StartSection("head") ?>
<!-- stuff that goes into the head tag of __layout.php -->
<?php View::EndSection(); ?>
