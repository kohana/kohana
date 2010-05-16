<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Welcome extends Controller {

	public function action_index()
	{
		// $keyword = 'test';
		//
		// $query = DB::select('id', 'title', 'tags')
		// 	->from('table')
		// 	->where('MATCH("title", "tags")', '', 'AGAINST(:keyword)')
		// 	->bind(':keyword', $keyword);
		//
		// echo Kohana::debug($query->compile(Database::instance()));

		// $feed = 'http://dev.kohanaframework.org/activity.atom?key=JXtiMOc7fJFxRssCOMrv8xo6J4GW3qDd6OjS45hy';
		// echo Kohana::debug(Feed::parse($feed));

		echo Request::factory('welcome/test')->execute();

		$this->request->response = View::factory('profiler/stats');
	}

	public function action_test()
	{
		$this->request->response = (string) View::factory('request')
			->render();
	}


} // End Welcome
