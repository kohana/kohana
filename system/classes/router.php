<?php

class Router_Core {

	protected $routes = array();

	public static function add($name, Route $route)
	{
		$this->routes[$name] = $route;
	}

} // End Kohana_Router
