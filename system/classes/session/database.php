<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Database-based session class.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Session_Database_Core extends Session {

	// Database table name
	protected $_table = 'sessions';

	// The current session id
	protected $_session_id;

	// The old session id
	protected $_update_id;

	// Update the session?
	protected $_update = FALSE;

	/**
	 * Loads database-specific configuration data.
	 *
	 * @param   array   configuration
	 * @return  void
	 */
	public function __construct(array $config = NULL)
	{
		if ( ! isset($config['group']))
		{
			// Use the default group
			$config['group'] = 'default';
		}

		// Load the database
		$this->_db = Database::instance($config['group']);

		if (isset($config['table']))
		{
			// Set the table name
			$this->_table = (string) $config['table'];
		}

		parent::__construct($config);
	}

	/**
	 * Loads the session data from the database.
	 *
	 * @param   string   session id
	 * @return  string
	 */
	public function _read($id = NULL)
	{
		if ($id OR $id = cookie::get($this->_name))
		{
			$result = DB::query(Database::SELECT, "SELECT data FROM {$this->_table} WHERE session_id = :id LIMIT 1")
				->set(':id', $id)
				->execute($this->_db);

			if ($result->count())
			{
				// Set the current session id
				$this->_session_id = $this->_update_id = $id;

				// Return the data string
				return $result->get('data');
			}
		}

		// Create a new session id
		$this->_regenerate();

		return NULL;
	}

	/**
	 * Generates a new unique session id.
	 *
	 * @return  string
	 */
	protected function _regenerate()
	{
		return $this->_session_id = uniqid(NULL, TRUE);
	}

	/**
	 * Inserts or updates the session in the database.
	 */
	protected function _write()
	{
		if ($this->_update_id === NULL)
		{
			// Insert a new row
			$query = DB::query(Database::INSERT, "INSERT INTO {$this->_table} (session_id, last_active, data) VALUES (:new_id, :active, :data)");
		}
		elseif ($this->_update_id === $this->_session_id)
		{
			// Update just the activity and data
			$query = DB::query(Database::UPDATE, "UPDATE {$this->_table} SET last_active = :active, data = :data WHERE session_id = :old_id");
		}
		else
		{
			// Update all fields
			$query = DB::query(Database::UPDATE, "UPDATE {$this->_table} SET session_id = :new_id, last_active = :active, data = :data WHERE session_id = :old_id");
		}

		$query
			->set(':new_id', $this->_session_id)
			->set(':old_id', $this->_update_id)
			->set(':active', $this->_data['last_active'])
			->set(':data', $this->__toString());

		try
		{
			// Execute the query
			$query->execute($this->_db);
		}
		catch (Exeception $e)
		{
			// Ignore all errors when a write fails
			return FALSE;
		}

		// The update and the session id are now the same
		$this->_update_id = $this->_session_id;

		// Update the cookie with the new session id
		cookie::set($this->_name, $this->_session_id, $this->_lifetime);

		return TRUE;
	}

} // End Session_Database
