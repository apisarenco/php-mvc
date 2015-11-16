<?php use PhpMvc\View; View::Layout("__layout.php");?>
<?php View::StartSection("head"); ?>

<?php View::EndSection(); ?>

<div class="row">
	<div class="col-md-12 page-404">
		<div class="number">
			404
		</div>
		<div class="details">
			<h3>Oops! You're lost.</h3>
			<p>
				We can not find the page you're looking for.<br/>
				<a href="/">
					Return home </a>
			</p>
		</div>
	</div>
</div>

<?php View::StartSection("tail"); ?>

<?php View::EndSection(); ?>
