<?php


namespace endiorite\mongo;

use endiorite\mongo\base\DataConnectorImpl;
use endiorite\mongo\base\MongoConfig;
use endiorite\mongo\result\MongoError;
use endiorite\mongo\thread\MongoThreadPool;
use MongoDB\Driver\ServerApi;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Terminal;

final class libAsyncMongo {


	/** @var bool */
	private static bool $packaged;

	public static function isPackaged() : bool{
		return self::$packaged;
	}

	public static function detectPackaged() : void{
		self::$packaged = __CLASS__ !== 'endiorite\mongo\libAsyncMongo';

		if(!self::$packaged && defined("pocketmine\\VERSION")){
			echo Terminal::$COLOR_YELLOW . "Warning: Use of unshaded libAsyncMongo detected. Debug mode is enabled. This may lead to major performance drop. Please use a shaded package in production. See https://poggit.pmmp.io/virion for more information.\n";
		}
	}
	public static function create(PluginBase $plugin, MongoConfig $configData) : DataConnectorImpl{
		require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
		libAsyncMongo::detectPackaged();

		$pool = new MongoThreadPool($configData);
		while(!$pool->connCreated()){
			usleep(1000);
		}
		if($pool->hasConnError()){
			throw new MongoError(MongoError::STAGE_CONNECT, $pool->getConnError());
		}
		return new DataConnectorImpl($plugin, $pool);
	}

}