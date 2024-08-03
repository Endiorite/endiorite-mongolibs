<?php

namespace endiorite\mongo\data;

use endiorite\mongo\exception\QueueShutdownException;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

class QuerySendQueue extends ThreadSafe
{
	/** @var bool */
	private bool $invalidated = false;
	/** @var ThreadSafeArray */
	private ThreadSafeArray $queries;

	public function __construct(){
		$this->queries = new ThreadSafeArray();
	}

	public function scheduleQuery(int $queryId, \Closure $closure, array $params, array $options): void {
		if($this->invalidated){
			throw new QueueShutdownException("You cannot schedule a query on an invalidated queue.");
		}
		$this->synchronized(function() use ($queryId, $params,$options, $closure) : void{
			$this->queries[] = ThreadSafeArray::fromArray([$queryId, $closure, serialize($params), serialize($options)]);
			$this->notifyOne();
		});
	}

	public function fetchQuery() : ?ThreadSafeArray {
		return $this->synchronized(function(): ?ThreadSafeArray {
			while($this->queries->count() === 0 && !$this->isInvalidated()){
				$this->wait();
			}
			return $this->queries->shift();
		});
	}

	public function invalidate() : void {
		$this->synchronized(function():void{
			$this->invalidated = true;
			$this->notify();
		});
	}

	/**
	 * @return bool
	 */
	public function isInvalidated(): bool {
		return $this->invalidated;
	}

	public function count() : int{
		return $this->queries->count();
	}
}