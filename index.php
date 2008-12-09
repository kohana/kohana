<?php
/**
 * The directory in which your application specific resources are located.
 * The application directory must contain the config/kohana.php file.
 * 
 * @see  http://docs.kohanaphp.com/install#application
 */
$application = 'application';

/**
 * The directory in which shared resources are located. Each module must be
 * contained within its own directory.
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

//
//                 END OF CONFIGURATION, DO NOT EDIT BELOW!
// ----------------------------------------------------------------------------
//

// Define the name of the front controller
define('FC_FILE', basename(__FILE__));

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
	include 'install'.EXT;
}
elseif (file_exists(APPPATH.'bootstrap'.EXT))
{
	// Load the custom bootstrap
	include APPPATH.'bootstrap'.EXT;
}
else
{
	// Load the default bootstrap
	include SYSPATH.'bootstrap'.EXT;
}
