<?php

namespace endiorite\mongo\base;

use endiorite\mongo\libAsyncMongo;
use endiorite\mongo\result\MongoError;
use endiorite\mongo\result\MongoResult;
use endiorite\mongo\thread\MongoThreadPool;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Terminal;

class DataConnectorImpl
{

	private array $handlers = [];

	public function __construct(
		Plugin $plugin,
		MongoThreadPool $threadPool
	)
	{
		$threadPool->setDataConnector($this);
	}



	/**
	 * @param array $queries
	 * @param array $args
	 * @param array $modes
	 * @param callable $handler
	 * @param callable|null $onError
	 */
	public function executeRequest(array $params, \Closure $query, callable $handler, ?callable $onError = null) : void{
		$queryId = self::$queryId++;
		$trace = libAsyncMongo::isPackaged() ? null : new Exception("(This is the original stack trace for the following error)");
		$this->handlers[$queryId] = function(MongoError|MongoResult $results) use ($handler, $onError, $trace){
			if($results instanceof MongoError){
				$this->reportError($onError, $results, $trace);
			}else{
				try{
					$handler($results->getValue());
				}catch(Exception $e){
					if(!libAsyncMongo::isPackaged()){
						$prop = (new ReflectionClass(Exception::class))->getProperty("trace");
						$newTrace = $prop->getValue($e);
						$oldTrace = $prop->getValue($trace);
						for($i = count($newTrace) - 1, $j = count($oldTrace) - 1; $i >= 0 && $j >= 0 && $newTrace[$i] === $oldTrace[$j]; --$i, --$j){
							array_pop($newTrace);
						}
						/** @noinspection PhpUndefinedMethodInspection */
						$prop->setValue($e, array_merge($newTrace, [
							[
								"function" => Terminal::$COLOR_YELLOW . "--- below is the original stack trace ---" . Terminal::$FORMAT_RESET,
							],
						], $oldTrace));
					}
					throw $e;
				}catch(Error $e){
					if(!libAsyncMongo::isPackaged()){
						$exceptionProperty = (new ReflectionClass(Exception::class))->getProperty("trace");
						$oldTrace = $exceptionProperty->getValue($trace);

						$errorProperty = (new ReflectionClass(Error::class))->getProperty("trace");
						$newTrace = $errorProperty->getValue($e);

						for($i = count($newTrace) - 1, $j = count($oldTrace) - 1; $i >= 0 && $j >= 0 && $newTrace[$i] === $oldTrace[$j]; --$i, --$j){
							array_pop($newTrace);
						}
						/** @noinspection PhpUndefinedMethodInspection */
						$errorProperty->setValue($e, array_merge($newTrace, [
							[
								"function" => Terminal::$COLOR_YELLOW . "--- below is the original stack trace ---" . Terminal::$FORMAT_RESET,
							],
						], $oldTrace));
					}
					throw $e;
				}
			}
		};

		$this->addQuery($queryId, $params, $query);
	}



	public function readResults(array &$callbacks, ?int $expectedResults) : void{
		if($expectedResults === null){
			$resultsList = $this->bufferRecv->fetchAllResults();
		}else{
			$resultsList = $this->bufferRecv->waitForResults($expectedResults);
		}
		foreach($resultsList as [$queryId, $results]){
			if(!isset($callbacks[$queryId])){
				throw new InvalidArgumentException("Missing handler for query #$queryId");
			}

			$callbacks[$queryId]($results);
			unset($callbacks[$queryId]);
		}
	}



	private function reportError(?callable $default, MongoError $error, ?Exception $trace) : void{
		if($default !== null){
			try{
				$default($error, $trace);
				$error = null;
			}catch(MongoError $err){
				$error = $err;
			}
		}
		if($error !== null){
			if ($this->logger === null)
				return ;
			$this->logger->error($error->getMessage());
			if($error->getQuery() !== null){
				$this->logger->debug("Query: " . $error->getQuery());
			}
			if($error->getArgs() !== null){
				$this->logger->debug("Args: " . json_encode($error->getArgs()));
			}
			if($trace !== null){
				$this->logger->debug("Stack trace: " . $trace->getTraceAsString());
			}
		}
	}




	public function waitAll() : void{
		while(!empty($this->handlers)){
			$this->readResults($this->handlers, count($this->handlers));
		}
	}

	public function checkResults() : void{
		$this->readResults($this->handlers, null);
	}


	private function addQuery(int $queryId, array $params, \Closure $query)
	{

	}

}