<?php

require_once('cache.class.php');
require_once('sql.class.php');

class Model {

	private $cache;
	private $master;
	private $slave;
	private $dataName;
	private $console;
	private $ignoreCache = false;

	private $NAME = 'model:';
	private $cacheTimeKey = '__time__:';

	public function __construct($dataName) {
		$this->dataName = $dataName;
		$this->console = Console::create($this->NAME . $dataName);
	}

	public function ignoreCache() {
		$this->ignoreCache = true;
	}

	public function setupCache($host, $port, $ttl) {

		if ($this->ignoreCache) {
			return;
		}

		$this->cache = new Cache($host, $port, $ttl);
	}

	public function setupMaster($type, $host, $dbName, $user, $password) {
		$this->master = new Sql($type, $host, $dbName, $user, $password, $this->dataName . '.master');
	}

	public function setupSlave($type, $host, $dbName, $user, $password) {
		$this->slave = new Sql($type, $host, $dbName, $user, $password, $this->dataName . '.slave');
	}

	public function read($sql, $params = array()) {

		$res = null;
		$tableNames = array();

		if (!$this->ignoreCache) {
			// try to get the data from cache
			$tableNames = $this->getTableNames($sql);
			$key = $this->dataName . ':' . implode('-', $tableNames) . ':' . implode('.', $params);
			$res = $this->cache->get($key);
		}

		if ($res) {
			// check the cached update time for this dataName
			$allClear = true;
			$keys = $this->createCacheTimeKeys($sql);
			for ($i = 0, $len = count($keys); $i < $len; $i++) {
				$time = $this->cache->get($keys[$i]);
				if (!$time || $time > $res['time']) {
					// if one of them is stale, we ignore cache
					$allClear = false;
				}
			}
			if ($allClear) {
				// there is cached data and the data is not stale

				$this->console->log('Data retrieved from cache:', $sql, implode(',', $params));

				return $res['data'];
			}
		}

		$res = $this->slave->read($sql, $params);

		if (!$this->ignoreCache) {
			// store cached data
			$time = $this->updateCacheTime($sql);
			$this->cache->set($key, array('data' => $res, 'time' => $time));
		}

		return $res;
	}

	public function readForWrite($sql, $params = array()) {
		return $this->master->read($sql, $params);
	}

	public function write($sql, $params = array()) {
		
		if (!$this->ignoreCache) {
			// update dataName time
			$this->updateCacheTime($sql);
		}

		// execute write query
		return $this->master->write($sql, $params);
	}

	public function transaction() {
		return $this->master->transaction();
	}

	public function commit() {
		return $this->master->commit();
	}

	public function rollback() {
		return $this->master->rollback();
	}

	private function updateCacheTime($sql) {
		$time = time();
		$keys = $this->createCacheTimeKeys($sql);
		for ($i = 0, $len = count($keys); $i < $len; $i++) {
			$this->cache->set($keys[$i], $time);
		}
		return $time;
	}

	private function getCacheTimeList($sql) {
		$res = array();
		$keys = $this->createCacheTimeKeys($sql);
		for ($i = 0, $len = count($keys); $i < $len; $i++) {
			$res[] = $this->cache->get($keys[$i]);
		}
		return $res;
	}

	private function createCacheTimeKeys($sql) {
		$keys = array();
		$tableNames = $this->getTableNames($sql);
		for ($i = 0, $len = count($tableNames); $i < $len; $i++) {
			$keys[] = $this->cacheTimeKey . $this->dataName . '.' . $tableNames[$i];
		}
		return $keys;
	}

	private function getTableNames($sql) {
		preg_match_all('/((?:^select .+?(?:from|into))|^update|^table|join) (`?\w+`?)\s/i', $sql, $matches);
		if (!isset($matches[2])) {
			return array();
		}
		return $matches[2];
	}

}
