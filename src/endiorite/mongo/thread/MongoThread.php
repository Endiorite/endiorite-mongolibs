<?php

namespace endiorite\mongo\thread;

use Closure;
use Composer\Autoload\ClassLoader;
use endiorite\mongo\base\MongoConfig;
use endiorite\mongo\data\QueryRecvQueue;
use endiorite\mongo\data\QuerySendQueue;
use endiorite\mongo\exception\QueueShutdownException;
use endiorite\mongo\libAsyncMongo;
use endiorite\mongo\result\MongoCursorResult;
use endiorite\mongo\result\MongoDeleteResult;
use endiorite\mongo\result\MongoError;
use endiorite\mongo\result\MongoFindResult;
use endiorite\mongo\result\MongoInsertResult;
use endiorite\mongo\result\MongoResult;
use endiorite\mongo\result\MongoUpdateResult;
use MongoDB\Client as MongoClient;
use MongoDB\DeleteResult;
use MongoDB\Driver\Cursor;
use MongoDB\InsertManyResult;
use MongoDB\InsertOneResult;
use MongoDB\Operation\ReplaceOne;
use MongoDB\UpdateResult;
use pmmp\thread\Thread as NativeThread;
use pmmp\thread\ThreadSafeArray;
use pocketmine\Server;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\Thread;
use Throwable;

class MongoThread extends Thread
{

	private static int $nextSlaveNumber = 0;

	private readonly int $slaveId;
	private bool $busy = false;
	protected bool $connCreated = false;
	protected ?string $connError = null;
	private string $uriOptions;
	private string $driverOptions;

	private string $url;

	public function __construct(
		private readonly SleeperHandlerEntry $sleeperEntry,
		private readonly QuerySendQueue      $bufferSend,
		private readonly QueryRecvQueue      $bufferRecv,
		MongoConfig                          $config
	)
	{
		$this->slaveId = self::$nextSlaveNumber++;
		$this->url = $config->getUri();
		$this->uriOptions = igbinary_serialize($config->getUriOptions());
		$this->driverOptions = igbinary_serialize($config->getDriverOptions());
		if(!libAsyncMongo::isPackaged()){
			/** @noinspection PhpUndefinedMethodInspection */
			/** @noinspection NullPointerExceptionInspection */
			/** @var ClassLoader $cl */
			$cl = Server::getInstance()->getPluginManager()->getPlugin("DEVirion")->getVirionClassLoader();
			$this->setClassLoaders([Server::getInstance()->getLoader(), $cl]);
		}
		$this->start(NativeThread::INHERIT_INI);
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
		require_once dirname(__DIR__, 4) . '/vendor/autoload.php';
		$notifier = $this->sleeperEntry->createNotifier();
		try {
			$client = $this->createConnection();
			$this->connCreated = true;
		} catch (Throwable $exception){
			$this->connError = $exception;
			$this->connCreated = true;
			return;
		}
		while(true) {
			$row = $this->bufferSend->fetchQuery();
			if (!($row instanceof ThreadSafeArray)) {
				break;
			}
			$this->busy = true;
			[$queryId, $closure, $params, $options] = $row;
			try{
				$params = unserialize($params, ["allowed_classes" => true]);
				$options = unserialize($options, ["allowed_classes" => true]);
				$this->bufferRecv->publishResult($queryId, $this->executeQuery($closure, $client,  $params,$options));
			}catch(MongoError $error){
				$this->bufferRecv->publishError($queryId, $error);
			}
			$notifier->wakeupSleeper();
			$this->busy = false;
		}
		$client = null;
	}

	public function executeQuery(Closure $execute, MongoClient $mongo, array $params, array $options): mixed
	{
		try {
			$data = $execute($mongo, $params);
			if ($data instanceof InsertManyResult || $data instanceof InsertOneResult){
				$result = new MongoInsertResult($data, $options);
			} else if ($data instanceof UpdateResult || $data instanceof ReplaceOne) {
				$result = new MongoUpdateResult($data, $options);
			} else if ($data instanceof DeleteResult) {
				$result = new MongoDeleteResult($data, $options);
			} else if ($data instanceof Cursor) {
				$result = new MongoCursorResult($data, $options);
			} else{
				$result = new MongoFindResult($data);
			}
			return $result;
		}catch (Throwable $throwable) {
			throw new MongoError(MongoError::STAGE_EXECUTE, $throwable->getMessage(), $params);
		}
	}

	public function stopRunning(): void {
		$this->bufferSend->invalidate();
		parent::quit();
	}

	/**
	 * @return int
	 */
	public function getSlaveId(): int
	{
		return $this->slaveId;
	}

	public function connCreated() : bool{
		return $this->connCreated;
	}

	public function hasConnError() : bool{
		return $this->connError !== null;
	}

	public function getConnError() : ?string{
		return $this->connError;
	}

	/**
	 * @return bool
	 */
	public function isBusy() : bool{
		return $this->busy;
	}

	public function quit() : void{
		$this->stopRunning();
		parent::quit();
	}
}