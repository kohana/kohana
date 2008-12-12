<?php

class Kohana_Config_Loader_Core extends ArrayObject {

	public function __construct()
	{
		ArrayObject::__construct(array(), ArrayObject::ARRAY_AS_PROPS);
	}

	public function save()
	{
		foreach ($this as $group => $config)
		{
			if ($config->changed())
			{
				// Cache the group
				Kohana::cache('kohana_config_'.$group, $config->getArrayCopy());
			}
		}

		return $this;
	}

	protected function load($group)
	{
		// Find all the configuration files
		$files = Kohana::find_file('config', $group);

		$config = array();
		foreach ($files as $file)
		{
			// Merge the config files together
			$config = array_merge($config, Kohana::load_file($file));
		}

		return $config;
	}

	public function offsetExists($group)
	{
		if ( ! parent::offsetExists($group))
		{
			if (($config = Kohana::cache('kohana_config_'.$group)) === NULL)
			{
				// Load the configuration group
				$config = $this->load($group);

				// Cache the configuration
				Kohana::cache('kohana_config_'.$group, $config);
			}

			// Set the missing configuration
			$this->offsetSet($group, new Kohana_Config($config));
		}

		return TRUE;
	}

	public function offsetGet($index)
	{
		$this->offsetExists($index);

		return parent::offsetGet($index);
	}

} // End Kohana_Config_Loader
