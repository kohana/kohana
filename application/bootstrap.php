<?php defined('SYSPATH') or die('No direct script access.');

//-- Environment setup --------------------------------------------------------

/**
 * Enable the Kohana auto-loader.
 */
spl_autoload_register(array('Kohana', 'auto_load'));

/**
 * Enable Kohana exception handling, adds stack traces and error source.
 */
set_exception_handler(array('Kohana', 'exception_handler'));

/**
 * Enable Kohana error handling, converts all PHP errors to exceptions.
 */
set_error_handler(array('Kohana', 'error_handler'));

//-- Kohana configuration -----------------------------------------------------

/**
 * Initialize Kohana, setting the default options.
 */
Kohana::init(array('charset' => 'utf-8', 'base_url' => '/ko3/index.php/'));

/**
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
Kohana::modules(array(
	// 'orm'      => MODPATH.'orm',
	'database' => MODPATH.'database',
	'todoist'  => MODPATH.'todoist',
	));

/**
 * Attach the file write to logging. Any Kohana_Log object can be attached,
 * and multiple writers are supported.
 */
Kohana::$log->attach(new Kohana_Log_File(APPPATH.'logs'));

/**
 * Set the language to use for translating.
 */
i18n::$lang = 'en_US';

//-- Routing and execution ----------------------------------------------------

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

/**
 * Execute the main request using PATH_INFO. If no URI source is specified,
 * the URI will be automatically detected.
 *
 * To return the output of the request instead of displaying it, specify
 * TRUE for execute().
 */
Request::instance($_SERVER['PATH_INFO'])
	->execute(FALSE);
