<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Welcome extends Controller {

	public function action_index()
	{
		$this->response->body('Hello world!')
			->headers('cache-control', HTTP_Header::create_cache_control(
				array(
					'max-age' => 15,
					'public'
				)
			));
	}


} // End Welcome
