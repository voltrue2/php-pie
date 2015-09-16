<?php

$path = __FILE__;

require_once(str_replace('index.php', '', $path) . 'src/model.class.php');

class DataSource {
	
	private static $modelMap = array();

	public static function create($name) {
		$model = new Model($name);
		self::$modelMap[$name] = $model;
	}

	public static function get($name) {
		if (isset(self::$modelMap[$name])) {
			return self::$modelMap[$name];
		}
		return null;
	}
}
