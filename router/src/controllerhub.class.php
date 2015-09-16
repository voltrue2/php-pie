<?php

class ControllerHub {

	private $name;
	private $method;
	private $params;
	private $queries;
	private $reqMethod;
	private $controller;
	private $request;
	private $response;
	private $error = null;
	private $console;

	private $CNT_CLS_NAME = 'Controller';

	public function __construct($controllerPath, $cntName, $methodName, $params, $req, $res) {
		$this->name = $cntName;
		$this->method = $methodName;
		$this->params = $params;
		$this->reqMethod = $_SERVER['REQUEST_METHOD'];
		$this->request = $req;
		$this->response = $res;

		$this->console = Console::create('router/controllerhub');

		// start operation
		$this->init($controllerPath);
		$this->run();
	}

	public function getError() {
		return $this->error;
	}

	private function init($path) {
		// load controller method
		$cntPath = $path . $this->name . '/' . $this->method . '.class.php';
		try {
			if (!file_exists($cntPath)) {
				throw new Exception('Controller or method not found: ' . $cntPath);
			}
			require_once($cntPath);
		} catch (Exception $exception) {
			$this->error = array(
				'code' => 404,
				'detail' => $exception
			);
			$this->console->error($cntPath, $this->error['code'], $this->error['detail']->getMessage());
			return;
		}
		// check request method against controller method
		$this->controller = new $this->CNT_CLS_NAME($this->params);
		if (!method_exists($this->controller, $this->reqMethod)) {
			$this->error = array(
				'code' => 405,
				'detail' => new Exception($this->reqMethod . ' not allowed [' . $this->name . '/' . $this->method . ']')
			);
			$this->console->error($cntPath, $this->error['code'], $this->error['detail']->getMessage());
			return;
		}
	}

	private function run() {

		// check for an error from $this->init();
		if ($this->error) {
			return;
		}

		try {
			// call the controller
			$cb = array($this->controller, $this->reqMethod);
			$args = array($this->request, $this->response, $this->params);
			call_user_func_array($cb, $args);
			// controller method has finished its operation, terminate
			exit();
		} catch (Exception $exception) {
			$this->error = array(
				'code' => 500,
				'detail' => $exception
			);
			$this->console->error($this->controller . '/' . $this->reqMethod, $this->error['code'], $this->error['detail']->getMessage());
		}
	}

}
