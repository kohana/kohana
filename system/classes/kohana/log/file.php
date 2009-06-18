<?php defined('SYSPATH') or die('No direct script access.');
/**
 * File log writer.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Kohana_Log_File extends Kohana_Log_Writer {

	// Directory to place log files in
	protected $_directory;

	/**
	 * Creates a new file logger.
	 *
	 * @param   string  log directory
	 * @return  void
	 */
	public function __construct($directory)
	{
		if ( ! is_dir($directory) OR ! is_writable($directory))
		{
			throw new Kohana_Exception('Directory :dir must be writable',
				array(':dir' => Kohana::debug_path($directory)));
		}

		// Determine the directory path
		$this->_directory = realpath($directory).'/';
	}

	/**
	 * Writes each of the messages into the log file.
	 *
	 * @param   array   messages
	 * @return  void
	 */
	public function write(array $messages)
	{
		// Set the monthly directory name
		$directory = $this->_directory.date('Y/m').'/';

		if ( ! is_dir($directory))
		{
			// Create the monthly directory
			mkdir($directory, 0777, TRUE);
		}

		// Set the name of the log file
		$filename = $directory.date('d').EXT;

		if ( ! file_exists($filename))
		{
			// Create the log file
			file_put_contents($filename, Kohana::FILE_SECURITY.' ?>'.PHP_EOL);

			// Allow anyone to write to log files
			chmod($filename, 0666);
		}

		// Set the log line format
		$format = 'time --- type: body';

		foreach ($messages as $message)
		{
			// Write each message into the log file
			file_put_contents($filename, PHP_EOL.strtr($format, $message), FILE_APPEND);
		}
	}

} // End Kohana_Log_File