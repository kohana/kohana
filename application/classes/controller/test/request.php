<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Test_Request extends Controller_Test {

	public function action_subreq()
	{
		foreach (array('main', 'globals') as $method)
		{
			$uri = "reqs/$method";
			$this->_cases[$uri] = Request::factory($uri)->execute();
		}
	}

} // End Test_Request