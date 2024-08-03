<?php

namespace endiorite\mongo\thread;

use Closure;
use endiorite\mongo\base\MongoConfig;
use endiorite\mongo\data\QueryRecvQueue;
use endiorite\mongo\data\QuerySendQueue;
use endiorite\mongo\result\MongoError;
use endiorite\mongo\result\MongoResult;
use http\Exception\InvalidArgumentException;
use MongoDB\Client as MongoClient;
use pmmp\thread\ThreadSafeArray;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\Thread;
use SenseiTarzan\SyncManager\Thread\data\RedisError;
use Throwable;

class MongoThread extends Thread
{

	private SleeperHandlerEntry $sleeperEntry;
	private bool $busy = false;
	private string $uriOptions;
	private string $driverOptions;

	private string $url;

	public function __construct(
		SleeperHandlerEntry $entry,
		private QuerySendQueue $bufferSend,
		private QueryRecvQueue $bufferRecv,
		MongoConfig $config
	)
	{
		$this->sleeperEntry = $entry;
		$this->url = $config->getUri();
		$this->uriOptions = igbinary_serialize($config->getUriOptions());
		$this->driverOptions = igbinary_serialize($config->getDriverOptions());
	}

	public function createConnection(): MongoClient
	{
		return new MongoClient(
			$this->url,
			(array)igbinary_unserialize($this->uriOptions),
			(array)igbinary_unserialize($this->driverOptions));
	}

	protected function onRun(): void
	{
		$notifier = $this->sleeperEntry->createNotifier();
		try {
			$client = $this->createConnection();
		} catch (Throwable $exception){
			throw new MongoError(MongoError::STAGE_CONNECT, $exception);
		}
		while(true) {
			$row = $this->bufferSend->fetchQuery();
			if (!($row instanceof ThreadSafeArray)) {
				break;
			}
			$this->busy = true;
			[$queryId, $params, $closure] = $row;
			try{
				$params = unserialize($params, ["allowed_classes" => true]);
				$this->bufferRecv->publishResult($queryId, new MongoResult($this->executeQuery($client, $params, $closure)));
			}catch(MongoError $error){
				$this->bufferRecv->publishError($queryId, $error);
			}
			$notifier->wakeupSleeper();
			$this->busy = false;
		}
		$client = null;
	}

	public function addQuery(int $queryId, array $params, Closure $query) : void{
		$this->bufferSend->scheduleQuery($queryId, $params, $query);
	}

	public function executeQuery(MongoClient $mongo, array $params, Closure $execute): mixed
	{
		try {
			return $execute($mongo, $params);
		}catch (Throwable $throwable) {
			throw new MongoError($throwable->getMessage(), $throwable->getCode(), $throwable);
		}
	}
}