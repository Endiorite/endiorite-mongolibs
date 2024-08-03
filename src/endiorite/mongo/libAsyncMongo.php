<?php


namespace endiorite\mongo;

use DateTime;
use endiorite\mongo\thread\MongoThreadPool;
use Exception;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Driver\ServerApi;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Terminal;

final class libAsyncMongo {


	/** @var bool */
	private static $packaged;

	public static function isPackaged() : bool{
		return self::$packaged;
	}

	public static function detectPackaged() : void{
		self::$packaged = __CLASS__ !== 'endiorite\mongo\libAsyncMongo';

		if(!self::$packaged && defined("pocketmine\\VERSION")){
			echo Terminal::$COLOR_YELLOW . "Warning: Use of unshaded libAsyncMongo detected. Debug mode is enabled. This may lead to major performance drop. Please use a shaded package in production. See https://poggit.pmmp.io/virion for more information.\n";
		}
	}
	public static function create(PluginBase $pluginBase, string $uri, int|ServerApi $serverApi = ServerApi::V1) {
		require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
		/*
		$client = new Client($uri, [], ['serverApi' => new ServerApi($serverApi)]);

		// test
		try {
			$client->selectDatabase("database-test")->command(['ping' => 1]);
			echo "Pong.\n";
		} catch(Exception $e) {
			printf("Erreur de connexion : %s\n", $e->getMessage());
			exit();
		}

		// Création de la collection
		$database = $client->selectDatabase("database-test");
		$collectionName = "collection-name";

		try {
			$database->createCollection($collectionName);
			echo "Collection créée : $collectionName\n";
		} catch(Exception $e) {
			printf("Erreur lors de la création de la collection : %s\n", $e->getMessage());
		}

		// Insertion d'un document avec un nom en string et une date de création en timestamp
		$collection = $database->selectCollection($collectionName);
		$document = [
			'nom' => 'Exemple de nom',
			'date_creation' => new UTCDateTime((new DateTime())->getTimestamp()*1000)
		];

		try {
			$result = $collection->insertOne($document);
			echo "Document inséré avec l'ID : " . $result->getInsertedId() . "\n";
		} catch(Exception $e) {
			printf("Erreur lors de l'insertion du document : %s\n", $e->getMessage());
		}*/
	}

}