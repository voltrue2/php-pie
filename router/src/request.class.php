<?php

class Request {

	private $source = array();
	private $headers = array();
	private $data = array();

	public function __construct($requestMethod) {
		switch ($requestMethod) {
			case 'GET':
			case 'HEAD':
				$this->source = $_GET;
				break;
			case 'POST':
				$this->source = $_POST;
				break;
			case 'PUT':
			case 'DELETE':
				parse_str(file_get_contents('php://input'), $this->source);
				break;
		}
		// sanitize input
		$this->source = $this->sanitize($this->source);
		// parse headers
		$this->headers = getallheaders();
		$this->uri = $_SERVER['REQUEST_URI'];
		$index = strpos($this->uri, '?');
		if ($index) {
			$this->uri = substr($this->uri, 0, $index);
		}
		$this->uri = trim($this->uri, '/');
	}

	public function set($name, $value) {
		$this->data[$name] = $value;
	}

	public function get($name) {
		return isset($this->data[$name]) ? $this->data[$name] : null;
	}

	public function getData($name) {
		if (isset($this->source[$name])) {
			return $this->source[$name];
		}
		return null;
	}

	public function getAllData() {
		return $this->source;
	}

	public function getHeader($name) {
		if (isset($this->headers[$name])) {
			return $this->headers[$name];
		}
		return null;
	}

	public function getAllHeaders() {
		return $this->headers;
	}

	public function getUri() {
		return $this->uri;
	}

	private function sanitize($data) {
		if (is_array($data)) {
			foreach ($data as $i => $val) {
				$data[$i] = $this->sanitize($val);
			}
			return $data;
		}
		return htmlspecialchars($data);
	}
}
