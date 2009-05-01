<?php defined('SYSPATH') or die('No direct script access.') ?>

<style type="text/css">
<?php include Kohana::find_file('views', 'kohana/profiler', 'css') ?>
</style>

<div class="kohana">
	<?php foreach (Profiler::groups() as $group => $benchmarks): ?>
	<table class="profiler">
		<tr class="group">
			<th class="name" colspan="5"><?php echo __(ucfirst($group)) ?></th>
		</tr>
		<?php foreach ($benchmarks as $name => $tokens): ?>
		<tr class="mark time">
			<th class="name" rowspan="2"><?php echo $name, ' (', count($tokens), ')' ?></td>
			<?php $stats = Profiler::stats($tokens); ?>
			<?php foreach (array('min', 'max', 'average', 'total') as $key): ?>
			<td class="<?php echo $key ?>"><?php echo number_format($stats[$key]['time'], 6), ' ', __('seconds') ?></td>
			<?php endforeach ?>
		</tr>
		<tr class="mark memory">
			<?php foreach (array('min', 'max', 'average', 'total') as $key): ?>
			<td class="<?php echo $key ?>"><?php echo number_format($stats[$key]['memory'] / 1024, 4), ' kb' ?></td>
			<?php endforeach ?>
		</tr>
		<?php endforeach ?>
	</table>
	<?php endforeach ?>
	<table class="profiler">
		<?php $stats = Profiler::application() ?>
		<tr class="final mark time">
			<th class="name" rowspan="2"><?php echo __('Application Execution').' ('.$stats['count'].')' ?></td>
			<?php foreach (array('min', 'max', 'average', 'total') as $key): ?>
			<td class="<?php echo $key ?>"><?php echo number_format($stats[$key]['time'], 6), ' ', __('seconds') ?></td>
			<?php endforeach ?>
		</tr>
		<tr class="final mark memory">
			<?php foreach (array('min', 'max', 'average', 'total') as $key): ?>
			<td class="<?php echo $key ?>"><?php echo number_format($stats[$key]['memory'] / 1024, 4), ' kb' ?></td>
			<?php endforeach ?>
		</tr>
	</table>
</div>
