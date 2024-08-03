<?php

namespace endiorite\mongo\result;

/**
 * This class automatically serializes values which can't be shared between threads.
 * This class does NOT enable sharing the variable between threads. Each call to deserialize() will return a new copy
 * of the variable.
 *
 * @phpstan-template TValue
 */
class MongoResult
{
	private string $variable;

	/**
	 * @phpstan-param TValue $variable
	 */
	public function __construct(
		mixed $variable
	){
		$this->variable = igbinary_serialize($variable) ?? throw new \InvalidArgumentException("Cannot serialize variable of type " . get_debug_type($variable));
	}

	/**
	 * Returns a deserialized copy of the original variable.
	 *
	 * @phpstan-return TValue
	 */
	public function deserialize() : mixed{
		return igbinary_unserialize($this->variable);
	}
}