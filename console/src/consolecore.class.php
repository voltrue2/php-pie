<?php

class ConsoleCore {

	private $filePath = null;
	private $noClient = false;
	private $name = null;

	public function __construct($filePath = null, $noClient = false, $name = null) {
		// optional to write to a file
		$this->filePath = $filePath;
		// optional to disable client logging
		$this->noClient = $noClient;
		// logger name
		$this->name = $name;
		// set up a fatal error catcher
		ExceptionHandler::add('logFatalError', $this);
	}

	public function log() {
		$args = func_get_args();
		$this->createLog('log', $args);
	}
	
	public function warn() {
		$args = func_get_args();
		$this->createLog('warn', $args);
	}
	
	public function error() {
		$args = func_get_args();
		$this->createLog('error', $args);
	}

	// this is public because of register_shutdown_function
	public function logFatalError($error) {
		$this->error('Exception: ', $error);
	}

	private function createLog($type, $list) {
		$time = date('Y/m/d H:i:s') . substr((string)microtime(), 1, 8);
		$tag = '<script type="text/javascript">';
		$tag .= 'window.console.' . $type . '(' . ($this->name ? '"[' . $time . ']{' . $this->name . '}",' : '');
		$tagVals = array();
		$fileLog = array();
		
		foreach ($list as $item) {
			$datatype = gettype($item);
			switch ($datatype) {
				case 'string':
					$tagVals[] = '"' . addslashes($item) . '"';
					$fileLog[] = $item;
					break;
				case 'array':
				case 'object':
					$json = json_encode($item);
					$tagVals[] = $json;
					$fileLog[] = $json;
					break;
				case 'boolean':
					$bool = $item ? 'true' : 'false';
					$tagVals[] = $bool;
					$fileLog[] = $bool;
					break;
				case 'NULL':
                                        $tagVals[] = 'null';
					$fileLog[] = 'null';
                                        break;
				default:
					$tagVals[] = $item;
					$fileLog[] = $item;
					break;
			}
		}
		
		$this->writeToFile($type, $time, $fileLog);
	
		$htmlLog = $tag .= implode(',', $tagVals) . ');</script>';
	
		Console::prepare('html', $htmlLog);
		Console::prepare('json', $fileLog);
	}

	private function writeToFile($type, $time, $msgList) {
		if ($this->filePath) {
			$msg = implode(' ', $msgList);
			$msg = '[' . $time . ']<' . $type . '>' . ($this->name ? '{' . $this->name . '}' : '') . ' ' . $msg;

			switch ($type) {
				case 'log':
					$msg = "\033[0;30m" . $msg . "\033[0m";
					break;
				case 'warn':
					$msg = "\033[0;35m" . $msg . "\033[0m";
					break;
				case 'error':
					$msg = "\033[0;31m" . $msg . "\033[0m";
					break;
			}

			error_log($msg . "\r\n", 3, $this->filePath);
		}
	}
}
