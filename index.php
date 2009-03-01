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

// Set the full path to the docroot
define('DOCROOT', realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR);

// Make the application relative to the docroot
if ( ! is_dir($application) AND is_dir(DOCROOT.$application))
	$application = DOCROOT.$application;

// Make the modules relative to the docroot
if ( ! is_dir($modules) AND is_dir(DOCROOT.$modules))
	$modules = DOCROOT.$modules;

// Make the system relative to the docroot
if ( ! is_dir($system) AND is_dir(DOCROOT.$system))
	$modules = DOCROOT.$system;

// Define the absolute paths for configured directories
define('APPPATH', realpath($application).DIRECTORY_SEPARATOR);
define('MODPATH', realpath($modules).DIRECTORY_SEPARATOR);
define('SYSPATH', realpath($system).DIRECTORY_SEPARATOR);

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
set_exception_handler(array('Kohana', 'exception_handler'));

// Enable the error-to-exception handler
set_error_handler(array('Kohana', 'error_handler'));

// i18n translation function
function __($string, array $values = NULL)
{
	if (i18n::$lang !== i18n::$default_lang)
	{
		// Get the translation for this string
		$string = i18n::get($string);
	}

	return empty($values) ? $string : strtr($string, $values);
}

// Bootstrap the application
require APPPATH.'bootstrap'.EXT;