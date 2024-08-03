<?php

namespace endiorite\mongo\data;

use endiorite\mongo\result\MongoError;
use endiorite\mongo\result\MongoResult;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

class QueryRecvQueue extends ThreadSafe
{
	private int $availableThreads = 0;

	private ThreadSafeArray $queue;

	public function __construct(){
		$this->queue = new ThreadSafeArray();
	}

	/**
	 * @param int $queryId
	 * @param MongoResult $result
	 */
	public function publishResult(int $queryId, MongoResult $result) : void{
		$this->synchronized(function() use ($queryId, $result) : void{
			$this->queue[] = serialize([$queryId, $result]);
			$this->notify();
		});
	}

	public function publishError(int $queryId, MongoError $error) : void{
		$this->synchronized(function() use ($error, $queryId) : void{
			$this->queue[] = serialize([$queryId, $error]);
			$this->notify();
		});
	}

	public function fetchResults(&$queryId, &$results) : bool{
		$row = $this->queue->shift();
		if(is_string($row)){
			[$queryId, $results] = unserialize($row, ["allowed_classes" => true]);
			return true;
		}
		return false;
	}

	/**
	 * @return array
	 */
	public function fetchAllResults(): array{
		return $this->synchronized(function(): array{
			$ret = [];
			while($this->fetchResults($queryId, $results)){
				$ret[] = [$queryId, $results];
			}
			return $ret;
		});
	}

	/**
	 * @return list<array{int, MongoError|MongoResult|null}>
	 */
	public function waitForResults(int $expectedResults): array{
		return $this->synchronized(function() use ($expectedResults) : array{
			$ret = [];
			while(count($ret) < $expectedResults){
				if(!$this->fetchResults($queryId, $results)){
					$this->wait();
					continue;
				}
				$ret[] = [$queryId, $results];
			}
			return $ret;
		});
	}
}