<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Welcome extends Controller {

	public function action_index()
	{
		$this->request->response = View::factory('welcome')
			->bind('c', $this);
	}

} // End Welcome Controller
