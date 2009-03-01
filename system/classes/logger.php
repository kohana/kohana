<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Message logging.
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Logger_Core {

	/**
	 * @var  string  format of timestamp added to each message
	 */
	public static $timestamp = 'Y-m-d H:i:s';

	/**
	 * Writes a new log message.
	 *
	 *     Logger::write('debug', 'Testing the creation of log messages');
	 *
	 * @param   string   type of log (debug, error, etc)
	 * @param   string   message to log
	 * @return  boolean
	 */
	public function write($type, $message)
	{
		// Set the log directory
		$directory = APPPATH.'logs/'.date('Y/m');

		if ( ! is_dir($directory))
		{
			// Create the log directory
			mkdir($directory, 0777, TRUE);
		}

		// Set the log filename
		$filename = $directory.date('/d').'.log.php';

		if ( ! file_exists($filename))
		{
			// Create the log file
			file_put_contents($filename, Kohana::PHP_HEADER.' ?>'.PHP_EOL);

			// Prevent external writes
			chmod($filename, 0644);
		}

		// Create the message timestamp
		$timestamp = date(Logger::$timestamp);

		// Add the message to the log
		return (bool) file_put_contents($filename, PHP_EOL."{$timestamp} --- {$type}: {$message}", FILE_APPEND);
	}

} // End Logger