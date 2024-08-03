<?php

namespace endiorite\mongo\result;
use MongoDB\InsertManyResult;
use MongoDB\InsertOneResult;

class MongoInsertResult extends MongoResult
{

	private string $type;
    private mixed $insertedId;
	private array $insertedIds;

    private bool $isAcknowledged;

	private int|null $insertedCount = null;

	public function __construct(InsertManyResult|InsertOneResult $result, array $options)
	{
		$this->type = $result instanceof InsertOneResult ? 'one' : 'many';
		if($result instanceof InsertManyResult) {
			$this->insertedIds = $result->getInsertedIds();
		} else {
			$this->insertedId = $result->getInsertedId();
		}
		if(in_array("insertedCount", $options))
			$this->insertedCount = $result->getInsertedCount();
		$this->isAcknowledged = $result->isAcknowledged();
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @return int|null
	 */
	public function getInsertedCount(): ?int
	{
		return $this->insertedCount;
	}

	/**
	 * @return mixed
	 */
	public function getInsertedId(): mixed
	{
		return $this->insertedId;
	}

	/**
	 * @return array
	 */
	public function getInsertedIds(): array
	{
		return $this->insertedIds;
	}


	/**
	 * @return bool
	 */
	public function isAcknowledged(): bool
	{
		return $this->isAcknowledged;
	}
}