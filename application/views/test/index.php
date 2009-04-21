<h1><?php echo ucwords($controller) ?> Tests</h1>

<ul>
<?php foreach ($tests as $url => $method): ?>
	<li><a href="<?php echo $url ?>"><?php echo $method ?></a></li>
<?php endforeach ?>
</ul>