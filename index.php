<?php
// Bootstrap the application - modify this path if required
require __DIR__.'/application/bootstrap.php';

// If installation file exists
if (file_exists(DOCROOT.'install'.EXT))
{
	// Load the installation check
	return include DOCROOT.'install'.EXT;
}

// If PHP build's server API is CLI
if (PHP_SAPI == 'cli')
{
	/**
	 * Attempt to load and execute minion.
	 */
	class_exists('Minion_Task') OR die('Please enable the Minion module for CLI support.');
	set_exception_handler(array('Minion_Exception', 'handler'));

	Minion_Task::factory(Minion_CLI::options())->execute();
}
else
{
	/**
	 * Execute the main request. A source of the URI can be passed, eg: $_SERVER['PATH_INFO'].
	 * If no source is specified, the URI will be automatically detected.
	 */
	echo Request::factory(TRUE, array(), FALSE)
		->execute()
		->send_headers(TRUE)
		->body();
}
