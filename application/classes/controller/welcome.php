<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Welcome extends Controller {

	public function action_index(Request $request, Response $response)
	{
		return $response->body('hello, world!');
	}

} // End Welcome
