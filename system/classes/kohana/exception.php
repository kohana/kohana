<?php defined('SYSPATH') or die('No direct access');
/**
 * Kohana exception class. Converts exceptions into HTML messages.
 * 
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Kohana_Exception_Core extends Exception {

	/**
	 * Inline exception handler, displays the error message, source of the
	 * exception, and the stack trace of the error.
	 * 
	 * @param   object   exception object
	 * @return  void
	 */
	public static function handle(Exception $e)
	{
		try
		{
			// Get the exception information
			$type    = get_class($e);
			$code    = $e->getCode();
			$message = $e->getMessage();
			$file    = $e->getFile();
			$line    = $e->getLine();

			if (Kohana::$is_cli)
			{
				// Just display the text of the exception
				echo "\n", $type, ' [ ', $code ,' ]: ', $message, ' ', $file, ' [ ', $line, ' ] ', "\n";

				return TRUE;
			}

			// Get the exception backtrace
			$trace = $e->getTrace();

			if ($e instanceof ErrorException AND version_compare(PHP_VERSION, '5.3', '<'))
			{
				// Work around for a bug in ErrorException::getTrace() that exists in
				// all PHP 5.2 versions. @see http://bugs.php.net/bug.php?id=45895
				for ($i = count($trace) - 1; $i > 0; --$i)
				{
					if (isset($trace[$i - 1]['args']))
					{
						// Re-position the args
						$trace[$i]['args'] = $trace[$i - 1]['args'];

						// Remove the args
						unset($trace[$i - 1]['args']);
					}
				}
			}

			// Get the source of the error
			$source = Kohana::debug_source($file, $line);

			// Generate a new error id
			$error_id = uniqid();

			// Start an output buffer
			ob_start();

			// Include the exception HTML
			include Kohana::find_file('views', 'kohana/error');

			// Display the contents of the output buffer
			echo ob_get_clean();

			return TRUE;
		}
		catch (Exception $e)
		{
			// Clean the output buffer if one exists
			ob_get_level() and ob_clean();

			// This can happen when the kohana error view has a PHP error
			echo $e->getMessage(), ' [ ', Kohana::debug_path($e->getFile()), ', ', $e->getLine(), ' ]';

			// Exit with an error status
			exit(1);
		}
	}

	/**
	 * Creates a new translated exception.
	 * 
	 * @param   string   error message
	 * @param   array    translation variables
	 * @return  void
	 */
	public function __construct($message, array $variables = NULL)
	{
		// Set the message
		$message = __($message, $variables);

		// Pass the message to the parent
		parent::__construct($message);
	}

} // End Kohana_Exception
