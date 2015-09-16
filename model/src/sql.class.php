<?php

class Sql {

	private $console;
	private $cn;

	public function __construct($type, $host, $dbname, $user, $password, $dataName) {
		// logger
		$name = $type . '.' . $dbname . ':' . $dataName;
		$this->console = Console::create($name);
		// connect to db
		$connect = $type . ':';
		$connect .= 'host=' . $host . ';';
		$connect .= 'dbname=' . $dbname;
		// use connection pooling
		$options = array(
			PDO::ATTR_PERSISTENT =>  true
		);

		$this->console->log(
			'Connecting:',
			$type,
			$user . '@' . $host . '.' . $dbname,
			'[password:' . ($password ? 'true' : 'false') . ']'
		);

		$this->cn = new PDO($connect, $user, $password, $options);
	}

	public function read($sql, $params = array()) {
		$st = $this->cn->prepare($sql);
		$st->execute($params);

		if ($st->errorCode() !== '00000') {
			$info = $st->errorInfo();
			throw new Exception($st->queryString . ': [error code:' . $st->errorCode() . '] ' . $info[2]);
		}

		$res = $st->fetchAll(PDO::FETCH_ASSOC);

		$this->console->log('[read]:', $sql, implode(',', $params), 'data [' . count($res) . ']');

		return $res;
	}

	public function write($sql, $params = array()) {
		try {
			$st = $this->cn->prepare($sql);
			$st->execute($params);

			if ($st->errorCode() !== '00000') {
				$info = $st->errorInfo();
				throw new Exception($st->queryString . ': [' . $st->errorCode() . '] ' . $info[2]);
			}
			
			$count = $st->rowCount();

			$this->console->log('[write]:', $sql, implode(',', $params), 'affected rows [' . $count . ']');
			
			// return the number of rows affected
			return $count;
		} catch (Exception $error) {
			if ($this->cn->inTransaction()) {
				
				$this->console->log('Auto-rollback on exception:', $sql, implode(',', $params), $error->getMessage());

				$this->rollback();
				return 0;
			}
			throw $error;
		}
	}

	public function transaction() {
		if ($this->cn->inTransaction()) {
			throw new Exception('Already in transaction');
		}
		
		$this->console->log('Start Transaction');

		$this->cn->beginTransaction();

		return true;
	}

	public function commit() {
		if ($this->cn->inTransaction()) {
			
			$this->console->log('Commit: End Transaction');

			$this->cn->commit();
			return true;
		}

		$this->console->error('Not in transaction: commit failed');
		
		return false;
	}

	public function rollback() {
		if ($this->cn->inTransaction()) {
			
			$this->console->error('Rollback: End Transaction');
	
			$this->cn->rollBack();
			return true;
		}

		$this->console->error('Not in transaction: rollback failed');

		return false;
	}	
}
