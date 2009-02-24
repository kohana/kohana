<?php defined('SYSPATH') or die('No direct script access.');

abstract class Controller_Core {

	public $request;

	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	public function before()
	{
		
	}

	public function after()
	{
		
	}

	public function render()
	{
		
	}

} // End Controller