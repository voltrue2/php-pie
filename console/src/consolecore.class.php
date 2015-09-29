<?php

class ConsoleCore {

	private $filePath = null;
	private $noClient = false;
	private $name = null;
	private $phpVersion;
	private $verbose = true;

	public function __construct($filePath = null, $noClient = false, $name = null, $verbose = true) {
		$this->phpVersion = phpversion();
		// optional to write to a file
		$this->filePath = $filePath;
		// optional to disable client logging
		$this->noClient = $noClient;
		// logger name
		$this->name = $name;
		// log level
		$this->verbose = $verbose;
		// set up a fatal error catcher
		ExceptionHandler::add('logFatalError', $this);
	}

	public function log() {
		if (!$this->verbose) {
			return;
		}
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
					if ($this->phpVersion >= 5.4) {
						// for PHP 5.4+
						$json = json_encode($item);
					} else {
						$pattern = "/\\\\u([a-f0-9]{4})/e";
						$option = "iconv('UCS-4LE','UTF-8',pack('V', hexdec('U$1')))";
						$json = preg_replace($pattern, $option, json_encode($item));
					}
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

		if (!$this->noClient) {	
			Console::prepare('html', $htmlLog);
			Console::prepare('json', $fileLog);
		}
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
