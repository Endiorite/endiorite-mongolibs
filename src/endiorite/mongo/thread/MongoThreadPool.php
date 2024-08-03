<?php


namespace endiorite\mongo\thread;

use endiorite\mongo\base\DataConnectorImpl;
use endiorite\mongo\data\QueryRecvQueue;
use endiorite\mongo\data\QuerySendQueue;
use endiorite\mongo\interface\IMongoThread;
use endiorite\mongo\libAsyncMongo;
use endiorite\mongo\result\MongoError;
use endiorite\mongo\result\MongoResult;
use Error;
use Exception;
use MongoDB\Client;
use pocketmine\Server;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\utils\Terminal;
use ReflectionClass;

class MongoThreadPool implements IMongoThread {

	private SleeperHandlerEntry $sleeperHandlerEntry;

	private static int $queryId = 0;

	/**
	 * @var MongoThread[]
	 */
	private array $workers = [];
	private QuerySendQueue $bufferSend;
	private QueryRecvQueue $bufferRecv;
	private DataConnectorImpl $dataConnector;


	/**
	 * @param DataConnectorImpl $dataConnector
	 */
	public function setDataConnector(DataConnectorImpl $dataConnector): void {
		$this->dataConnector = $dataConnector;
	}
	public function __construct(
		private \AttachableLogger $logger,
		private string $url,
		private int $workerLimit = 1
	) {
		$this->sleeperHandlerEntry = Server::getInstance()->getTickSleeper()->addNotifier(function(): void {
			assert($this->dataConnector instanceof DataConnectorImpl); // otherwise, wtf
			$this->dataConnector->checkResults();
		});
	}
	public function stopRunning(): void
	{
		$this->bufferSend->invalidate();
	}

	public function quit(): void
	{
		$this->stopRunning();
	}
	private function addQuery(int $queryId, array $params, \Closure $query)
	{

	}

}