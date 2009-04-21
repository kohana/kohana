<h1>Unicode Translating</h1>

<table>
	<tr>
		<th>Code</th>
		<th>Language</th>
		<th>Phrase</th>
	</tr>
	<?php foreach ($cases as $lang => $name): i18n::$lang = $lang; ?>
	<tr>
		<td><?php echo $lang ?></td>
		<td><?php echo __($name) ?></td>
		<td><?php echo __('Hello, world') ?></td>
	</tr>
	<?php endforeach ?>
</table>
