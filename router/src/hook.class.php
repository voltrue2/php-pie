<?php

class Hook {
	
	private $console;
	private $map = array();

	public function __construct($name) {
		$this->console = Console::create($name . '.hook');
	}

	// class maybe an instance of a class or a string name of a static class
	// a callback MUST return a HTTP error code (403 etc) for an error
	// if there's no error return null
	public function add($cnt, $method, $funcName, $class = null) {
		$uri = $cnt . ($method ? '/' . $method : '');
		if (!isset($this->map[$uri])) {
			$this->map[$uri] = array();
		}
		$this->map[$uri][] = array(
			'class' => $class,
			'func' => $funcName
		);
		if ($method === 'index') {
			if (!isset($this->map[$cnt])) {
				$this->map[$cnt] = array();
			}
			$this->map[$cnt][] = array(
				'class' => $class,
				'func' => $funcName
			);
		}
	}

	public function call($cnt, $method, $req, $res) {
		$uri = $cnt . ($method ? '/' . $method : '');
		$list = array();
		$list = isset($this->map[$cnt]) ? $this->map[$cnt] : array();
		$list = array_merge($list, isset($this->map[$uri]) ? $this->map[$uri] : array());
		if (empty($list)) {
			return;
		}
		try {
			$doneList = array();
			for ($i = 0, $len = count($list); $i < $len; $i++) {
				$item = $list[$i];
				$callback = $item['func'];
				if ($item['class']) {
					$callback = array(
						$item['class'],
						$item['func']
					);
				}
				// ignore the hook that has been executed
				if (in_array($callback, $doneList)) {
					continue;
				}
				$this->console->log('Executing a hook:', $callback);
				$res = call_user_func_array($callback, array($req, $res));
				if ($res) {
					if ($res > 400) {
						$this->console->error('hook error [/' . $cnt . '/' . $method . ']:', $res);
						return $res;
					}
					$this->console->log('hook', $callback, 'OK');
				}
				// remember the hook executed
				$doneList[] = $callback;
				
			}
		} catch (Exception $e) {
			$this->console->error('Hook exception [' . $uri . ']:', $e);
			return 500;
		}
		// no hooks
		return null;
	}

}
