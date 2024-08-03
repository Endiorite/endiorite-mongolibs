<?php


namespace endiorite\mongo\thread;

use Closure;
use endiorite\mongo\base\DataConnectorImpl;
use endiorite\mongo\base\MongoConfig;
use endiorite\mongo\data\QueryRecvQueue;
use endiorite\mongo\data\QuerySendQueue;
use endiorite\mongo\exception\QueueShutdownException;
use endiorite\mongo\interface\IMongoThread;
use MongoDB\Client;
use pocketmine\Server;
use pocketmine\snooze\SleeperHandlerEntry;

class MongoThreadPool implements IMongoThread {

	private SleeperHandlerEntry $sleeperHandlerEntry;
	/**
	 * @var MongoThread[]
	 */
	private array $workers = [];
	private QuerySendQueue $bufferSend;
	private QueryRecvQueue $bufferRecv;
	private DataConnectorImpl $dataConnector;

	private int $workerCount = 0;

	private int $workerLimit;


	/**
	 * @param DataConnectorImpl $dataConnector
	 */
	public function setDataConnector(DataConnectorImpl $dataConnector): void {
		$this->dataConnector = $dataConnector;
	}
	public function __construct(
		private readonly MongoConfig $config
	) {
		$this->sleeperHandlerEntry = Server::getInstance()->getTickSleeper()->addNotifier(function(): void {
			assert($this->dataConnector instanceof DataConnectorImpl); // otherwise, wtf
			$this->dataConnector->checkResults();
		});
		$this->bufferSend = new QuerySendQueue();
		$this->bufferRecv = new QueryRecvQueue();
		$this->workerLimit = $this->config->getWorkerLimit();
		$this->addWorker();
	}

	public function addWorker(): void
	{
		++$this->workerCount;
		$this->workers[] = new MongoThread($this->sleeperHandlerEntry, $this->bufferSend, $this->bufferRecv, $this->config);
	}
	public function stopRunning(): void
	{
		//$this->bufferSend->invalidate();
		/** @var MongoThread[] $worker */
		foreach($this->workers as $worker) {
			$worker->stopRunning(); // $this->bufferSend->invalidate();
		}
	}

	public function quit(): void
	{
		$this->stopRunning();
	}

	/**
	 * @param int $queryId
	 * @param Closure $closure
	 * @param array $params
	 * @param array $options
	 * @return void
	 * @throws QueueShutdownException
	 */
	public function addQuery(int $queryId, Closure $closure, array $params, array $options) : void{
		$this->bufferSend->scheduleQuery($queryId, $closure, $params, $options);

		// check if we need to increase worker size
		foreach($this->workers as $worker){
			if(!$worker->isBusy()){
				return;
			}
		}
		if($this->workerCount < $this->workerLimit){
			$this->addWorker();
		}
	}

	public function join(): void {
		/** @var MongoThread[] $worker */
		foreach($this->workers as $worker) {
			$worker->join();
		}
	}

	public function readResults(array &$callbacks, ?int $expectedResults): void {
		if($expectedResults === null ){
			$resultsLists = $this->bufferRecv->fetchAllResults();
		} else {
			$resultsLists = $this->bufferRecv->waitForResults($expectedResults);
		}
		foreach($resultsLists as [$queryId, $results]) {
			if(!isset($callbacks[$queryId])) {
				throw new \InvalidArgumentException("Missing handler for query (#$queryId)");
			}
			$callbacks[$queryId]($results);
			unset($callbacks[$queryId]);
		}
	}

	public function connCreated() : bool{
		return $this->workers[0]->connCreated();
	}

	public function hasConnError() : bool{
		return $this->workers[0]->hasConnError();
	}

	public function getConnError() : ?string{
		return $this->workers[0]->getConnError();
	}

	public function getLoad() : float{
		return $this->bufferSend->count() / (float) $this->workerLimit;
	}

}