<?php

namespace endiorite\mongo\result;

readonly class MongoFindResult
{

	public function __construct(
		private array|object|null $result
	)
	{
	}

	public function getResult(): object|array|null {
		return $this->result;
	}
}