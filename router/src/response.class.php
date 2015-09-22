<?php

require_once('data.class.php');

class Response {
	
	private $console;

	public function __construct() {
		$this->console = Console::create('router/response');
	}

	public function assign($name, $value) {
		if (is_numeric($value)) {
			$value = (int)$value;
		}
		Data::set($name, $value);
	}

	// must be an absolute path
	public function html($sourcePath, $code = 200) {
		if (file_exists(Loader::getRootPath() . $sourcePath)) {
			ob_start();
			include(Loader::getRootPath() . $sourcePath);
			$data = ob_get_contents();
			ob_end_clean();
			header('Content-Type: text/html; charset=UTF-8');
			header('Content-Length: ' . strlen($data));
			header('HTTP/1.1 ' . Router::status($code));
			echo implode('', Console::output('html'));
			echo $data;
			exit();
		}
		header('Content-Type: text/html; charset=UTF-8');
		header('Content-Length: ' . strlen($sourcePath));
		header('HTTP/1.1 ' . Router::status($code));
		echo implode('', Console::output('html'));
		echo $sourcePath;
		exit();
	}

	public function json($code = 200) {
		Data::set('logger', Console::output('json'));
		$string = json_encode(Data::getAll());
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-Type: application/json');
		header('Content-Encoding: gzip');
		header('HTTP/1.1 ' . Router::status($code));
		ob_start('ob_gzhandler');
		header('Content-Length: ' . strlen($string));
		echo $string;
		ob_end_flush();
		exit();
	}

	public function redirect($uri, $status = 301) {
		$this->console->log('Redirect:', $uri, '[' . $status . ']');
		Router::redirect($uri, $status);
	}
}
