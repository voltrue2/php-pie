<?php

class Loader {

	private static $path = '';

	public static function setRootPath($path) {
		self::$path = $path;
	}

	public static function get($path) {
		require_once(self::$path . $path);
	}

	public static function getRootPath() {
		return self::$path;
	}
}
