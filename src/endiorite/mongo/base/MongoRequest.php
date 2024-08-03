<?php

namespace endiorite\mongo\base;

readonly class MongoRequest
{
	public function __construct(
		private array $params = [],
		private array $optionsResult = []
	)
	{
	}

	public static function create(array $params = [], array $optionsResult = []): self
	{
		return new self($params, $optionsResult);
	}

	/**
	 * @return array
	 */
	public function getParams(): array
	{
		return $this->params;
	}


	/**
	 * @return array
	 */
	public function getOptionsResult(): array
	{
		return $this->optionsResult;
	}

}