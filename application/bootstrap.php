<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Enable modules.
 */
Kohana::modules(array(
	'database' => MODPATH.'database'));

/**
 * Add routes.
 */
Route::set('static', 'static/<page>', array('page' => '.+'))
	->defaults(array(
		'controller' => 'pages',
		'action' => 'load'));

Route::set('default', '(<controller>(/<action>(/<id>)))')
	->defaults(array(
		'controller' => 'welcome',
		'action' => 'index',
		'id' => NULL));

$q = Request::instance(@$_SERVER['PATH_INFO']);

$q->process();
