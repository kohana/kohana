<h1>Getting Cookies</h1>

<table>
	<tr>
		<th>Name</th>
		<th>Raw Value</th>
		<th>Values</th>
	</tr>
	<?php foreach ($cases as $name => $values): ?>
	<tr>
		<td><?php echo $name ?></td>
		<td><pre style="width:300px;overflow:auto;padding:1em"><?php echo $_COOKIE[$name] ?></code></td>
		<td>
			<table>
				<?php foreach ($values as $key => $val): ?>
				<tr>
					<th><?php echo $key ?></th>
					<td><code><?php echo $val ?></code></td>
				</tr>
				<?php endforeach ?>
			</table>
		</td>
	</tr>
	<?php endforeach ?>
</table>
