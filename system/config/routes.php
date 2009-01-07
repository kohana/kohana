<?php

return array
(
	'default' =>
		Route::factory('(<controller>(/<method>(/<id>)))')
			->defaults(array('controller' => 'welcome', 'method' => 'index')),
);
