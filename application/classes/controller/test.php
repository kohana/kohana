<?php defined('SYSPATH') or die('No direct script access.');

abstract class Controller_Test extends Controller {

	protected $_name;
	protected $_tests;
	protected $_cases;

	public function action_index()
	{
		// Get all methods
		$methods = get_class_methods($this);

		// Start a new test list
		$tests = array();

		foreach ($methods as $method)
		{
			if (strpos($method, 'action_') === 0 AND $method !== 'action_index')
			{
				// Remove "action" prefix
				$method = substr($method, 7);

				// Add the method to the test list
				$tests[$this->request->url(array('action' => $method))] = $method;
			}
		}

		// Get the controller name
		$controller = inflector::humanize(substr(get_class($this), 16));

		// Show the test list
		$this->request->response = View::factory('test/index')
			->set('controller', $controller)
			->set('tests', $tests);
	}

	public function after($method)
	{
		if ($this->request->action !== 'index')
		{
			$this->request->response = View::factory($this->request->uri)
				->bind('cases', $this->_cases);
		}
	}

} // End Test