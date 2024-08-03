<?php

namespace endiorite\mongo\result;
use MongoDB\InsertManyResult;
use MongoDB\InsertOneResult;
use MongoDB\UpdateResult;

class MongoUpdateResult extends MongoResult
{

    private bool $isAcknowledged;

	private int|null $matchedCount = null;
	private int|null $modifiedCount = null;
	private int|null $upsertedCount = null;
	private int|null $upsertedId = null;

	public function __construct(UpdateResult $result, array $options)
	{
		if (in_array("matchedCount", $options))
			$this->matchedCount = $result->getMatchedCount();
		if (in_array("modifiedCount", $options))
			$this->modifiedCount = $result->getModifiedCount();
		if (in_array("upsertedCount", $options))
			$this->upsertedCount = $result->getUpsertedCount();
		if (in_array("ppsertedId", $options))
			$this->upsertedId = $result->getUpsertedId();
		$this->isAcknowledged = $result->isAcknowledged();
	}


	/**
	 * @return int|null
	 */
	public function getMatchedCount(): ?int
	{
		return $this->matchedCount;
	}

	/**
	 * @return int|null
	 */
	public function getModifiedCount(): ?int
	{
		return $this->modifiedCount;
	}

	/**
	 * @return int|null
	 */
	public function getUpsertedCount(): ?int
	{
		return $this->upsertedCount;
	}

	/**
	 * @return int|null
	 */
	public function getUpsertedId(): ?int
	{
		return $this->upsertedId;
	}


	/**
	 * @return bool
	 */
	public function isAcknowledged(): bool
	{
		return $this->isAcknowledged;
	}
}