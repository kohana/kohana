<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Error_Core extends Exception {

	protected $codes = array
	(
		E_ERROR              => 'Error',
		E_USER_ERROR         => 'User Error',
		E_PARSE              => 'Parse Error',
		E_WARNING            => 'Warning',
		E_USER_WARNING       => 'User Warning',
		E_STRICT             => 'Strict',
		E_NOTICE             => 'Notice',
		E_RECOVERABLE_ERROR  => 'Recoverable Error',
	);

	protected $severity;

	public function __construct($message, $code, $severity, $filename, $line)
	{
		$this->message  = $message;
		$this->code     = isset($this->codes[$code]) ? $this->codes[$code] : $code;
		$this->severity = $severity;
		$this->file     = $filename;
		$this->line     = $line;
	}

	public function getSeverity()
	{
		return $this->severity;
	}

} // End Kohana_Error
