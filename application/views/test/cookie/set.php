<h1>Setting Cookies</h1>

<table>
	<tr>
		<th>Name</th>
		<th>Values</th>
	</tr>
	<?php foreach ($cases as $name => $values): ?>
	<tr>
		<td><?php echo $name ?></td>
		<td>
			<table>
				<?php foreach ($values as $key => $val): ?>
				<tr>
					<th><?php echo $key ?></th>
					<td><?php echo $val ?></td>
				</tr>
				<?php endforeach ?>
			</table>
		</td>
	</tr>
	<?php endforeach ?>
</table>
