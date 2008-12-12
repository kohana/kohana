<?php
/**
 * The directory in which your application specific resources are located.
 * The application directory must contain the config/kohana.php file.
 * 
 * @see  http://docs.kohanaphp.com/install#application
 */
$application = 'application';

/**
 * The directory in which the Kohana resources are located. The system
 * directory must contain the classes/kohana.php file.
 * 
 * @see  http://docs.kohanaphp.com/install#system
 */
$system = 'system';

/**
 * Modules are additional resource paths. Any file that can be placed within
 * the application or system directories can also be placed in a module.
 * All modules are relative or absolute paths to directories.
 * 
 * @see  http://docs.kohanaphp.com/modules
 */
$modules = 'modules';

/**
 * Modules are additional resource paths. Any file that can be placed within
 * the application or system directories can also be placed in a module.
 * All modules are relative or absolute paths to directories.
 * 
 * @see  http://docs.kohanaphp.com/modules
 */
$modules = array
(
	'modules/database',
	'modules/forms',
	'modules/email',
);

/**
 * The default extension of resource files. If you change this, all resources
 * must be renamed to use the new extension.
 * 
 * @see  http://docs.kohanaphp.com/install#ext
 */
define('EXT', '.php');

//
//                 END OF CONFIGURATION, DO NOT EDIT BELOW!
// ----------------------------------------------------------------------------
//

// Define the name of the front controller
define('FC_FILE', basename(__FILE__));

// Define the absolute paths for configured directories
define('DOCROOT', str_replace('\\', '/', realpath(getcwd())).'/');
define('APPPATH', str_replace('\\', '/', realpath($application)).'/');
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
