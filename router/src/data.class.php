<?php

class Data {

	private static $data = array();
	
	public static function set($name, $value) {
		self::$data[$name] = $value;
	}

	public static function get($name) {
		return isset(self::$data[$name]) ? self::$data[$name] : '{"' .$name . '":<NoValue>}';
	}

	public static function getAll() {
		return self::$data;
	}
	
}
