<?php

namespace endiorite\mongo\result;

readonly class MongoResult
{
	public function __construct(
		private array|object|null $value
	){
	}

	public function getValue(): array|null|object
	{
		return $this->value;
	}
}