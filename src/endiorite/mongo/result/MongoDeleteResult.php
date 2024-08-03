<?php

namespace endiorite\mongo\result;
use MongoDB\DeleteResult;

class MongoDeleteResult extends MongoResult
{

	private bool $isAcknowledged;

	private int|null $deletedCount = null;

	public function __construct(DeleteResult $result, array $options)
	{
		if (in_array("deletedCount", $options))
			$this->deletedCount = $result->getDeletedCount();
		$this->isAcknowledged = $result->isAcknowledged();
	}

	/**
	 * @return int|null
	 */
	public function getDeletedCount(): ?int
	{
		return $this->deletedCount;
	}
	/**
	 * Return whether this delete was acknowledged by the server.
	 *
	 * If the delete was not acknowledged, other fields from the WriteResult
	 * (e.g. deletedCount) will be undefined.
	 *
	 * @return boolean
	 */
	public function isAcknowledged(): bool
	{
		return $this->isAcknowledged;
	}
}