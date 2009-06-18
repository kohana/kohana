<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Model base class.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
abstract class Kohana_Model {

	// Database instance
	protected $_db = 'default';

	/**
	 * Loads the database.
	 * 
	 * @return  void
	 */
	public function __construct()
	{
		if (is_string($this->_db))
		{
			// Load the database
			$this->_db = Database::instance($this->_db);
		}
	}

} // End Model
