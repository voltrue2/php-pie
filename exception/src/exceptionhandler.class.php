<?php

class ExceptionHandler {
	
	private static $list = array();

	public static function init() {
		// PHP 5.2+
                register_shutdown_function(array('ExceptionHandler', 'handle'));
	}

	public static function add($funcName, $class = null) {
		self::$list[] = array(
			'class' => $class,
			'func' => $funcName
		);
	}

	public static function handle() {
		$error = error_get_last();
		if ($error) {
			foreach (self::$list as $i => $item) {
				$callback = $item['func'];
				if ($item['class']) {
					$callback = array(
						$item['class'],
						$item['func']
					);
				}
				call_user_func($callback, $error);
			}
		}
	}

}
