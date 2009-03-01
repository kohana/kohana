<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Initialize Kohana
 */
Kohana::init(array('charset' => 'utf-8'));

/**
 * Enable modules.
 */
Kohana::modules(array(
	'database' => MODPATH.'database'));

/**
 * Set the language to use for translating.
 */
i18n::$lang = 'en_US';

/**
 * Set the routes.
 */
Route::set('default', '(<controller>(/<action>(/<id>)))')
	->defaults(array(
		'controller' => 'welcome',
		'action' => 'index',
		'id' => NULL));

// Execute the main request
Request::instance($_SERVER['PATH_INFO'])->execute(FALSE);

