<?php
class Encrypt {
	
	// bigger the cost slower this method becomes
	// how to validate the hash: crypt($str, $hash) === $storedHash
	public static function createHash($str) {
		// create salt
		$cost = 10;
		$encrypted = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
		$encoded = base64_encode($encrypted);
		$translated = strtr($encoded, '+', '.');
		// prefix salt for PHP to validate later with crypt function
		// $2a$ means we are using Blowfish algorithm
		$salt = sprintf('$2a$%02d$', $cost) . $translated;
		$hash = crypt($str, $salt);
		return $hash;
	}

	public static function validateHash($str, $strHash) {
		// hash $str with its hash as the salt returns the same hash
		return crypt($str, $strHash) === $strHash;
	}
}
