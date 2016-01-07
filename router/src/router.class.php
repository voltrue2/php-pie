<?php

require_once('request.class.php');
require_once('response.class.php');
require_once('hook.class.php');
require_once('controllerhub.class.php');

class Router {

	private $uriPrefix = '';	
	private $trailingSlash = false;
	private $controllerPath = null;
	/*
	array(
		'from' => '/',
		'to' => '/example'
	)
	*/
	private $rerouteMap = array();
	/*
	array(
		'404' => '/error/notFound',
		'500' => '/error/internalError'
	)
	*/
	private $errorRerouteMap = array();
	/*
	array(
		'/example',
		'/example2'
	)
	*/
	private $noTrailingSlashMap = array();
	private $uri;
	private $reqHook;
	private $console;

	private static $STATUS = array(
		200 => '200 OK',
		300 => '300 Multiple Choices',
		301 => '301 Moved Permanently',
		302 => '302 Found',
		304 => '304 Not Modified',
		400 => '400 Bad Request',
		401 => '401 Unauthorized',
		403 => '403 Forbidden',
		404 => '404 Not Found',
		405 => '405 Method Not Allowed',
		500 => '500 Internal Server Error',
		502 => 'Bad Gateway',
		503 => '503 Service Unavailable'
	);

	public function __construct() {
		$this->console = Console::create('router');
		$this->reqHook = new Hook('request');
		$this->uri = $_SERVER['REQUEST_URI'];
		// make sure to get rid of query string
		$pos = strpos($this->uri, '?');
		if ($pos !== false) {
			$this->uri = substr($this->uri, 0, $pos);
		}
		// register exception handler
		ExceptionHandler::add('respondException', $this);
	}

	public static function status($code) {
		if (isset(self::$STATUS[$code])) {
			return self::$STATUS[$code];
		}
		return self::$STATUS[200];
	}
	
	public static function redirect($uri, $statusCode = 301) {
		// encode url parameters
		$sep = explode('?', $uri);
		if (isset($sep[1])) {
			// there are some GET params
			$uri = $sep[0] . '?';
			$list = explode('&', $sep[1]);
			$encodedList = array();
			for ($i = 0, $len = count($list); $i < $len; $i++) {
				$s = explode('=', $list[$i]);
				$encodedList[] = $s[0] . '=' . urlencode($s[1]);
			}
			$uri .= implode('&', $encodedList);
		}	
		// status code
		$status = isset(self::$STATUS[$statusCode]) ? self::$STATUS[$statusCode] : self::$STATUS[301];
		header('HTTP/1.1 ' . $status);
		header('Location: ' . $uri);
		exit();
	}

	public function setUriPrefix($prefix) {
		$this->uriPrefix = trim($prefix, '/');
	}

	public function setTrailingSlash($enable) {
		$this->trailingSlash = $enable;
	}

	public function setControllerPath($path) {
		$this->controllerPath = $path;
	}

	public function addErrorReroute($code, $errorUri) {
		if ($errorUri !== '/') {
			$errorUri = trim($errorUri, '/');
		}
		$sep = explode('/', $errorUri);
		$this->errorRerouteMap[$code] = array(
			'controller' => $sep[0],
			'method' => isset($sep[1]) ? $sep[1] : 'index'
		);
		// make sure the error URI is not accessable otherwise
		$this->addReroute($errorUri, '/');
	}

	public function addReroute($from, $to) {
		// trim slash
		if ($from !== '/') {
			$from = trim($from, '/');
		}
		if ($to !== '/') {
			$to = trim($to, '/');
		}
		$this->rerouteMap[$from] = $to;
	}

	public function addRequestHook($uri, $func, $class = null) {
		$parsed = $this->parseUri($uri);
		$this->reqHook->add($parsed['controllerName'], $parsed['methodName'], $func, $class);		
	}

	public function run() {
		// do we need to force trailing slash?
		if ($this->trailingSlash) {
			$this->forceTrailingSlash();
		}
		// extract controller and method
		$parsed = $this->parseUri($this->uri);
		$controllerName = $parsed['controllerName'];
		$methodName = $parsed['methodName'];
		$params = $parsed['params'];

		$this->console->log('Request ' . $_SERVER['REQUEST_METHOD'] . ' [' . $this->uri . '] resolved:', $parsed);

		// create request and response objects
		$req = new Request($_SERVER['REQUEST_METHOD']);
		$res = new Response();

		// check for the request hooks
		$hookRes = $this->reqHook->call($controllerName, $methodName, $req, $res);
		if ($hookRes && $hookRes >= 400) {
			// request hook had an error
			$this->handleError($req, $res, $hookRes, $params);
			return;
		}
		// create controler and run it
		$cntHub = new ControllerHub($this->controllerPath, $controllerName, $methodName, $params, $req, $res);
		// check for errors
		// if we come here, it means there was something wrong
		$error = $cntHub->getError();
		if ($error) {
			// handle error such as 404, 500 etc...
			$this->handleError($req, $res, $error['code'], $params);
		}
	}

	private function parseUri($uri) {
		// remove URI prefix if set
                if ($uri !== '/') {
                        $uri = str_replace($this->uriPrefix, '', $uri);
                }
                // parse controller and method
		$sep = explode('/', substr(trim($uri, '/'), 0));
		if ($sep[0] !== '') {
			$controllerName = $sep[0];
			$methodName = isset($sep[1]) ? $sep[1] : 'index';
		} else {
			// URI is /
			$controllerName = '';
			$methodName = '';
		}
		$params = array_splice($sep, 2);
		// check for reroute
		if (isset($this->rerouteMap[$controllerName . '/' . $methodName])) {
			$reroutedUri = $this->rerouteMap[$controllerName . '/' . $methodName];
			return $this->parseUri($reroutedUri . '/' . (!empty($params) ? implode('/', $params) : ''));
		}
		return array(
			'controllerName' => $controllerName,
			'methodName' => $methodName,
			'params' => $params
		);
	}

	private function forceTrailingSlash() {
		$queries = '';
		$lastChar = substr($this->uri, strlen($this->uri) - 1);

		if ($lastChar !== '/') {
			// check for no trailing slash controller
			$sep = explode('/', substr(trim($this->uri, '/'), 0));
			$controller = $sep[0];
			if (in_array($controller, $this->noTrailingSlashMap)) {
				// this controller will be ignored
				return;
			}
			// force trailing slash
			if ($_SERVER['QUERY_STRING']) {
				$queries = '?' . $_SERVER['QUERY_STRING'];
			}
			$this->redirect($this->uri . '/' . $queries);
		}
	}

	private function handleError($req, $res, $code, $params = array(), $error = null) {
		if (isset($this->errorRerouteMap[$code])) {
			$handle = $this->errorRerouteMap[$code];
			$cntHub = new ControllerHub($this->controllerPath, $handle['controller'], $handle['method'], $params, $req, $res);
			exit();	
		}
		// no error handle defined
		header('HTTP/1.1 ' . self::$STATUS[$code]);
		echo 'Error: ' . $code . '<br />';
		if ($error) {
			echo var_dump($error);
		}
		exit();
	}

	// this is used privately, but because it's called from register_shutdown_function, it must be public
	public function respondException($error) {
		$this->console->error('Exception Response:', $error);			
		$this->handleError(new Request($_SERVER['REQUEST_METHOD']), new Response(), 500, null, $error);
	}

}
