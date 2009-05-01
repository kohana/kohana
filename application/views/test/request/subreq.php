<h1>Sub-Requests Text</h1>

<?php foreach ($cases as $uri => $response): ?>
<p>Requested <code><?php echo $uri ?></code></p>
<blockquote><?php echo $response ?></blockquote>
<?php endforeach ?>
