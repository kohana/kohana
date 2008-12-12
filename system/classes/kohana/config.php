<?php

class Kohana_Config extends ArrayObject {

	// Has this object been changed?
	protected $changed = FALSE;

	// The upper parent of this object
	protected $parent;

	/**
	 * Creates a new configuration object.
	 * 
	 * @param   array   array to convert
	 * @param   object  parent of this object
	 * @return  void
	 */
	public function __construct($array, $parent = NULL)
	{
		if ($parent === NULL)
		{
			// The parent object is this object
			$parent = $this;
		}

		// Set the parent object
		$this->parent = $parent;

		parent::__construct($this->array_to_config($array), ArrayObject::ARRAY_AS_PROPS);
	}

	/**
	 * Acts as a getter and setter for the changed status of this object.
	 * 
	 * @param   boolean  new status
	 * @return  boolean
	 */
	public function changed($status = NULL)
	{
		if ($status === TRUE OR $status === FALSE)
		{
			$this->changed = $status;
		}

		return $this->changed;
	}

	/**
	 * Recursively converts all of the arrays within an array to config objects.
	 * 
	 * @param   array   array to convert
	 * @return  array
	 */
	protected function array_to_config($array)
	{
		foreach ($array as $key => $value)
		{
			if (is_array($value))
			{
				$array[$key] = new Kohana_Config($value, $this->parent);
			}
		}
		return $array;
	}

	/**
	 * ArrayObject::getArrayCopy, recursively convert config objects to arrays.
	 * 
	 * @return   array
	 */
	public function getArrayCopy()
	{
		$array = array();
		foreach ($this as $key => $value)
		{
			if (is_object($value) AND $value instanceof Kohana_Config)
			{
				// Convert the value to an array, recursion
				$value = $value->getArrayCopy();
			}

			$array[$key] = $value;
		}
		return $array;
	}

	/**
	 * ArrayObject::exchangeArray, recursively converts arrays to config objects.
	 */
	public function exchangeArray($array)
	{
		return parent::exchangeArray($this->array_to_config($array));
	}

	/**
	 * ArrayObject::offsetExists, forces missing indexes to filled with NULL.
	 * 
	 * @param   string   array key name
	 * @return  boolean
	 */
	public function offsetExists($index)
	{
		if ( ! parent::offsetExists($index))
		{
			$this->offsetSet($index, NULL);
		}

		return TRUE;
	}

	/**
	 * ArrayObject::offsetGet, checks if offsets exists before returning them.
	 * 
	 * @param   string   array key name
	 * @return  mixed
	 */
	public function offsetGet($index)
	{
		// This will force missing values to
		$this->offsetExists($index);

		return parent::offsetGet($index);
	}

	/**
	 * ArrayObject::offsetSet, converts array values to config objects.
	 * 
	 * @param   string   array key name
	 * @param   mixed    new value
	 * @return  void
	 */
	public function offsetSet($index, $newval)
	{
		if (is_object($newval) AND $newval instanceof Kohana_Config)
		{
			// Simplify the object back to an array
			$newval = $newval->getArrayCopy();
		}

		if (is_array($newval))
		{
			// Convert the array into a config object
			$newval = new Kohana_Config($newval, $this->parent);
		}

		// Notify the parent that values have changed
		$this->parent->changed(TRUE);

		return parent::offsetSet($index, $newval);
	}

	public function offsetUnset($index)
	{
		if (parent::offsetExists($index))
		{
			// Notify the parent that values have changed
			$this->parent->changed(TRUE);
		}

		return parent::offsetUnset($index);
	}

} // End Kohana_Config
