<?php

class Config {

	private static $configData = array();

	public static function set($name, $value) {
		self::$configData[$name] = $value;
	}

	public static function get($name) {
		if (isset(self::$configData[$name])) {
			return self::$configData[$name];
		}
		return null;
	}
}
