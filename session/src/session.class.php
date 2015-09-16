<?php

class Session {
	
	private static $domain;
	private static $prefix;
	private static $mem;
	private static $SESSION_NAME = '__fsn__';
	// unix timestamp
	private static $ttl;
	private static $console;

	public static function setup($domain, $prefix, $host, $port, $ttl) {
		self::$domain = $domain;
		self::$prefix = $prefix;
		self::$console = Console::create('session');
		self::$ttl = $ttl;
		self::$mem = new Memcache();
		self::$mem->pconnect($host, $port);
		
		self::$console->log('Connecting:', $host . ':' . $port, '[ttl:' . $ttl . 's]');
	}

	public static function get() {
		$sid = self::getSid();

		if (!$sid) {
			return null;
		}

		$res = self::$mem->get($sid);
	
		self::$console->log('[get]:', '"' . $sid . '"', '[data:' . ($res ? 'true' : 'false') . ']');

		if ($res) {
			// update session
			self::set($res);
		}

		return $res;
	}

	public static function set($value) {
		$sid = self::$prefix . '@' . Uid::create();
		self::$mem->set($sid, $value, MEMCACHE_COMPRESSED, self::$ttl);

		self::$console->log('[set]:', '"' . $sid . '"', $value, self::$domain, '[ttl:' . self::$ttl . 's]');

		setcookie(self::$SESSION_NAME, $sid, time() + self::$ttl, '/', self::$domain);
		return $sid;
	}

	public static function delete() {
		$sid = self::getSid();
		
		if (!$sid) {
			return null;
		}

		$res = self::$mem->delete($sid);
		
		self::$console->log('[delete]:', '"' . $sid . '"');

		setcookie(self::$SESSION_NAME, $sid, time() - self::$ttl, '/', self::$domain);
		return $res;
	}

	private static function getSid() {
		if (isset($_COOKIE[self::$SESSION_NAME])) {
			$sid = $_COOKIE[self::$SESSION_NAME];
			if (substr($sid, 0, strpos($sid, '@')) === self::$prefix) {
				return $sid;
			}
		}
		return null;
	}
}
