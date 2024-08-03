<?php

namespace endiorite\mongo\result;
use MongoDB\Driver\Cursor;
use MongoDB\UpdateResult;

class MongoCursorResult extends MongoResult
{

	private array|null $data = null;
	private int $id;

	public function __construct(array|Cursor $result, array $options)
	{
		$this->id = $result->getId();
		if (in_array("toArray", $options, true)) {
			$this->data = $result->toArray();
		}
	}

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @return array|object
	 */
	public function getData(): object|array
	{
		return $this->data;
	}
}