<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Initialize Kohana
 */
Kohana::init(array('charset' => 'utf-8', 'base_url' => '/ko3/'));

/**
 * Enable modules.
 */
Kohana::modules(array(
	// 'orm'      => MODPATH.'orm',
	// 'database' => MODPATH.'database',
	'todoist'  => MODPATH.'todoist',
	));

/**
 * Log all messages to files
 */
Kohana::$log->attach(new Kohana_Log_File(APPPATH.'logs'));

/**
 * Set the language to use for translating.
 */
i18n::$lang = 'en_US';

/**
 * Set the routes.
 */

Route::set('test', 'test/(<controller>(/<action>))')
	->defaults(array(
		'directory'  => 'test',
		'controller' => 'list',
		'action'     => 'index'));

Route::set('default', '(<controller>(/<action>(/<id>)))')
	->defaults(array(
		'controller' => 'welcome',
		'action' => 'index',
		'id' => NULL));

// Execute the main request
Request::instance($_SERVER['PATH_INFO'])->execute(FALSE);
