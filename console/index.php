<?php
$path = __FILE__;

require_once(str_replace('index.php', '', $path) . 'src/consolecore.class.php');

class Console {
	
	private static $filePath;
	private static $noClient;
	private static $verbose;
	private static $logData = array(
		'html' => array(),
		'json' => array()
	);

	public static function setup($filePath = null, $noClient = false, $verbose = true) {
		self::$filePath = $filePath;
		self::$noClient = $noClient;
		self::$verbose = $verbose;
		self::create();
	}

	public static function create($name = null) {
		return new ConsoleCore(self::$filePath, self::$noClient, $name, self::$verbose); 
	}

	public static function prepare($type, $logData) {
		self::$logData[$type][] = $logData;
	}

	public static function output($type) {
		$logData = self::$logData[$type];
		self::$logData = array(
			'html' => array(),
			'json' => array()
		);
		return $logData;
	}

}
