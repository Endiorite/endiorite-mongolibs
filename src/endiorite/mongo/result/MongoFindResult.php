<?php

namespace endiorite\mongo\result;

class MongoFindResult extends MongoResult
{

	public function __construct(
		private readonly array|object|null $result
	)
	{
	}

	public function getResult(): object|array|null {
		return $this->result;
	}
}