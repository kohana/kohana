<h1>Unicode Text</h1>

<table>
	<tr>
		<th>Function</th>
		<th>Text</th>
		<th>PHP Output</th>
		<th>utf8 Output</th>
	</tr>
	<?php foreach ($cases as $func => $text): ?>
	<tr>
		<td><?php echo $func ?></td>
		<td><?php echo $text ?>
		<td><?php echo $func($text) ?></td>
		<td><?php echo call_user_func(array('utf8', $func), $text) ?></td>
	</tr>
	<?php endforeach ?>
</table>
