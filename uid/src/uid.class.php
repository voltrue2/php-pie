<?php

class Uid {

	// requires mod_unique_id in Apache
	public static function create() {
		$serverUid = isset($_SERVER['UNIQUE_ID']) ? $_SERVER['UNIQUE_ID'] : null;
		$ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : null;
		$phpUid = uniqid(mt_rand(0, 300), true);
		$source = $serverUid . $ip . $phpUid;
		$uidSource = hash('sha256', $source);
		return base64_encode($uidSource);
	}

}
