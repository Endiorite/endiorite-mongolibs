<?php


namespace endiorite\mongo\thread;

use endiorite\mongo\interface\SqlThread;
use pocketmine\Server;
use pocketmine\snooze\SleeperHandlerEntry;

class MongoThreadPool implements SqlThread {

	private SleeperHandlerEntry $sleeperHandlerEntry;

	public function __construct() {
		$this->sleeperHandlerEntry = Server::getInstance()->getTickSleeper()->addNotifier(function(): void {

		});

	}

}