<?php


namespace endiorite\mongo\thread;

use endiorite\mongo\data\QuerySendQueue;
use endiorite\mongo\interface\MongoThread;
use pocketmine\Server;
use pocketmine\snooze\SleeperHandlerEntry;

class MongoThreadPool implements MongoThread {

	private SleeperHandlerEntry $sleeperHandlerEntry;

	private static int $queryId = 0;

	/**
	 * @var array<MongoThread>
	 */
	private array $workers;
	private QuerySendQueue $bufferSend;
	private QueryRecvQueue $bufferRecv;

	private array $handlers = [];
	public function __construct(
		private string $url,
		private int $maxWorker = 1
	) {
		$this->sleeperHandlerEntry = Server::getInstance()->getTickSleeper()->addNotifier(function(): void {
		});

	}

	public function stopRunning(): void
	{

	}

}