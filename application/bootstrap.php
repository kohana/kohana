<?php defined('SYSPATH') or die('No direct script access.');

//-- Environment setup --------------------------------------------------------

/**
 * Set the default time zone.
 *
 * @see  http://docs.kohanaphp.com/features/localization#time
 * @see  http://php.net/timezones
 */
date_default_timezone_set('America/Chicago');

/**
 * Enable the Kohana auto-loader.
 *
 * @see  http://docs.kohanaphp.com/features/autoloading
 * @see  http://php.net/spl_autoload_register
 */
spl_autoload_register(array('Kohana', 'auto_load'));

/**
 * Enable Kohana exception handling, adds stack traces and error source.
 *
 * @see  http://docs.kohanaphp.com/features/exceptions
 * @see  http://php.net/set_exception_handler
 */
set_exception_handler(array('Kohana', 'exception_handler'));

/**
 * Enable Kohana error handling, converts all PHP errors to exceptions.
 *
 * @see  http://docs.kohanaphp.com/features/exceptions
 * @see  http://php.net/set_error_handler
 */
set_error_handler(array('Kohana', 'error_handler'));

//-- Kohana configuration -----------------------------------------------------

/**
 * Initialize Kohana, setting the default options.
 *
 * The following options are available:
 * - base_url:   path, and optionally domain, of your application
 * - index_file: name of your index file, usually "index.php"
 * - charset:    internal character set used for input and output
 * - profile:    enable or disable internal profiling
 * - caching:    enable or disable internal caching
 */
Kohana::init(array('charset' => 'utf-8', 'base_url' => '/ko3/'));

/**
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
Kohana::modules(array(
	// 'database'   => MODPATH.'database',   // Database access
	// 'image'      => MODPATH.'image',      // Image manipulation (not complete)
	// 'orm'        => MODPATH.'orm',        // Object Relationship Mapping (not complete)
	// 'pagination' => MODPATH.'pagination', // Paging of results (not complete)
	// 'paypal'     => MODPATH.'paypal',     // PayPal integration (not complete)
	// 'todoist'    => MODPATH.'todoist',    // Todoist integration
	// 'codebench'  => MODPATH.'codebench',  // Benchmarking tool
	));

/**
 * Attach the file write to logging. Any Kohana_Log object can be attached,
 * and multiple writers are supported.
 */
Kohana::$log->attach(new Kohana_Log_File(APPPATH.'logs'));

/**
 * Set the routes. Each route must have a minimum of a name, a URI and a set of
 * defaults for the URI.
 */
Route::set('default', '(<controller>(/<action>(/<id>)))')
	->defaults(array(
		'controller' => 'welcome',
		'action' => 'index',
		'id' => NULL));

/**
 * Execute the main request using PATH_INFO. If no URI source is specified,
 * the URI will be automatically detected.
 */
echo Request::instance($_SERVER['PATH_INFO'])
	->execute()
	->send_headers()
	->response;
