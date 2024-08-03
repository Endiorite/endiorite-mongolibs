<?php

namespace endiorite\mongo\base;

use Closure;
use endiorite\mongo\exception\QueueShutdownException;
use endiorite\mongo\libAsyncMongo;
use endiorite\mongo\result\MongoError;
use endiorite\mongo\result\MongoResult;
use endiorite\mongo\thread\MongoThreadPool;
use Error;
use Exception;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Terminal;
use ReflectionClass;

class DataConnectorImpl
{

	private array $handlers = [];
	private static int $queryId = 0;
	private readonly \AttachableLogger $logger;

	public function __construct(
		Plugin $plugin,
		private readonly MongoThreadPool $threadPool
	)
	{
		$this->logger = $plugin->getLogger();
		$threadPool->setDataConnector($this);
	}


	/**
	 * @param array $params
	 * @param array $options
	 * @param Closure $query
	 * @param callable $handler
	 * @param callable|null $onError
	 * @throws QueueShutdownException
	 */
	public function executeRequest(MongoRequest $mongoRequest, Closure $query, ?callable $handler = null, ?callable $onError = null) : void{
		$queryId = self::$queryId++;
		$trace = libAsyncMongo::isPackaged() ? null : new Exception("(This is the original stack trace for the following error)");
		$this->handlers[$queryId] = function(MongoError|MongoResult $results) use ($handler, $onError, $trace){
			if($results instanceof MongoError){
				$this->reportError($onError, $results, $trace);
			}else{
				if ($handler === null)
					return ;
				try{
					$handler($results);
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

		$this->threadPool->addQuery($queryId, $query, $mongoRequest->getParams(), $mongoRequest->getOptionsResult());
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
			$this->logger->error($error->getMessage());
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
			$this->threadPool->readResults($this->handlers, count($this->handlers));
		}
	}

	public function checkResults() : void{
		$this->threadPool->readResults($this->handlers, null);
	}

}