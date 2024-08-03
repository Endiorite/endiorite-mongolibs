<?php

namespace endiorite\mongo\base;

class MongoConfig
{
	public function __construct(
		private string $uri,
		private array $uriOptions,
		private array $driverOptions
	)
	{
	}

	/**
	 * @return string
	 */
	public function getUri(): string
	{
		return $this->uri;
	}

	/**
	 * @return array
	 */
	public function getUriOptions(): array
	{
		return $this->uriOptions;
	}

	/**
	 * @return array
	 */
	public function getDriverOptions(): array
	{
		return $this->driverOptions;
	}

}