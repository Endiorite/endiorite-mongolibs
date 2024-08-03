<?php

namespace endiorite\mongo\base;

class MongoConfig
{
	public function __construct(
		private string $uri,
		private array $uriOptions,
		private array $driverOptions,
		private int $workerLimit = 1
	)
	{
	}

	public static function parse(array $config): MongoConfig
	{
		return new self($config['uri'], $config['uri_options'] ?? [], $config['driver_options'] ?? [], $config['worker_limit'] ?? 1);
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

	/**
	 * @return int
	 */
	public function getWorkerLimit(): int
	{
		return $this->workerLimit;
	}

}