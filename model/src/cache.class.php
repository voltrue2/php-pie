<?php

class Cache {
	
	private $mem;
	// unix timestamp
	private $ttl;
	private $console;

	public function __construct($host, $port, $ttl) {
		$this->console = Console::create('cache');
		$this->ttl = $ttl;
		$this->mem = new Memcache();
		$this->mem->pconnect($host, $port);
		
		$this->console->log('Connecting:', $host . ':' . $port, '[ttl:' . $ttl . 's]');
	}

	public function get($key) {
		$res = $this->mem->get($key);
	
		$this->console->log('[get]:', '"' . $key . '"', '[data:' . ($res ? 'true' : 'false') . ']');

		return $res;
	}

	public function set($key, $value) {
		$res = $this->mem->set($key, $value, MEMCACHE_COMPRESSED, $this->ttl);

		$this->console->log('[set]:', '"' . $key . '"', $value, '[ttl:' . $this->ttl . 's]');

		return $res;
	}

	public function delete($key) {
		$res = $this->mem->delete($key);
		
		$this->console->log('[delete]:', '"' . $key . '"');

		return $res;
	}
}
