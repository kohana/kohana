<?php

$application = 'application';

$modules = 'modules';

$system = 'system';

define('EXT', '.php');

define('DOCROOT', str_replace('\\', '/', pathinfo(__FILE__, PATHINFO_DIRNAME)).'/');
define('APPPATH', str_replace('\\', '/', realpath($application)).'/');
define('MODPATH', str_replace('\\', '/', realpath($modules)).'/');
define('SYSPATH', str_replace('\\', '/', realpath($system)).'/');

unset($application, $modules, $system);

if (file_exists('install'.EXT))
{
	// Installation check
	include 'install'.EXT;
}
elseif (file_exists(APPPATH.'bootstrap'.EXT))
{
	// Custom boostrap
	include APPPATH.'bootstrap'.EXT;
}
else
{
	// Default bootstrap
	include SYSPATH.'bootstrap'.EXT;
}
