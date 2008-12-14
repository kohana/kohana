<?php
/**
 * The directory in which your application specific resources are located.
 * The application directory must contain the config/kohana.php file.
 *
 * @see  http://docs.kohanaphp.com/install#application
 */
$application = 'application';

/**
 * The directory in which your modules are located.
 *
 * @see  http://docs.kohanaphp.com/install#modules
 */
$modules = 'modules';

/**
 * The directory in which the Kohana resources are located. The system
 * directory must contain the classes/kohana.php file.
 *
 * @see  http://docs.kohanaphp.com/install#system
 */
$system = 'system';

/**
 * The default extension of resource files. If you change this, all resources
 * must be renamed to use the new extension.
 *
 * @see  http://docs.kohanaphp.com/install#ext
 */
define('EXT', '.php');

/**
 * End of standard configuration! Changing any of the code below should only be
 * attempted by those with a working knowledge of Kohana internals.
 *
 * @see  http://docs.kohanaphp.com/bootstrap
 *
 * ----------------------------------------------------------------------------
 */

// Define the name of the front controller index
define('FCINDEX', basename(__FILE__));

// Define the absolute paths for configured directories
define('DOCROOT', str_replace('\\', '/', realpath(getcwd())).'/');
define('APPPATH', str_replace('\\', '/', realpath($application)).'/');
define('MODPATH', str_replace('\\', '/', realpath($modules)).'/');
define('SYSPATH', str_replace('\\', '/', realpath($system)).'/');

// Clean up the configuration vars
unset($application, $modules, $system);

if (file_exists('install'.EXT))
{
	// Load the installation check
	return include 'install'.EXT;
}

// Load the main Kohana class
require SYSPATH.'classes/kohana'.EXT;

// Enable auto-loading of classes
spl_autoload_register(array('Kohana', 'auto_load'));

// Enable the exception handler
// set_exception_handler(array('Kohana', 'exception_handler'));

// Enable the error-to-exception handler
set_error_handler(array('Kohana', 'error_handler'));

// Initialize the environment
Kohana::init();

// Create the main instance
Kohana::instance();

// Shutdown the environment
Kohana::shutdown();
