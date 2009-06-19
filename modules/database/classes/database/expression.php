<?php defined('SYSPATH') or die('No direct script access.');

class Database_Expression {

	// Raw expression string
	protected $_value;

	/**
	 * Sets the expression string.
	 */
	public function __construct($value)
	{
		// Set the expression string
		$this->_value = $value;
	}

	/**
	 * Return the string value of the expression.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return (string) $this->_value;
	}

} // End Database_Expression