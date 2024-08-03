<?php

namespace endiorite\mongo\result;

use RuntimeException;

class MongoError extends RuntimeException {
	/**
	 * Returned by {@link SqlError::getStage() getStage()}, indicating that an error occurred while connecting to the database
	 */
	public const STAGE_CONNECT = "CONNECT";
	/**
	 * Returned by {@link SqlError::getStage() getStage()}, indicating that an error occurred while preparing the query
	 */
	public const STAGE_EXECUTE = "EXECUTION";
	/**
	 * Returned by {@link SqlError::getStage() getStage()}, indicating that an error occurred while handling the response of the query
	 */
	public const STAGE_RESPONSE = "RESPONSE";

	private string $stage;
	private string $errorMessage;
	private ?string $query;
	private ?array $args;

	public function __construct(string $stage, string $errorMessage, string $query = null, array $args = null){
		$this->stage = $stage;
		$this->errorMessage = $errorMessage;
		$this->query = $query;
		$this->args = $args;

		parent::__construct("SQL $stage error: $errorMessage" . ($query === null ? "" : (", for query $query | " . json_encode($args))));
		$this->flattenTrace();
	}

	/**
	 * Returns the stage of query execution at which the error occurred.
	 *
	 * @return string
	 */
	public function getStage() : string{
		return $this->stage;
	}

	/**
	 * Returns the error message
	 *
	 * @return string
	 */
	public function getErrorMessage() : string{
		return $this->errorMessage;
	}

	/**
	 * Returns the original query
	 *
	 * @return string|null
	 */
	public function getQuery() : ?string{
		return $this->query;
	}

	/**
	 * Returns the original arguments passed to the query
	 *
	 * @return mixed[]|null
	 */
	public function getArgs() : ?array{
		return $this->args;
	}

	/**
	 * Flattens the trace such that the exception can be serialized
	 *
	 * @see https://gist.github.com/Thinkscape/805ba8b91cdce6bcaf7c Exception flattening solution by Artur Bodera
	 */
	protected function flattenTrace() : void{
		$traceProperty = (new \ReflectionClass(\Exception::class))->getProperty('trace');
		$flatten = static function(&$value){
			if($value instanceof \Closure){
				$closureReflection = new \ReflectionFunction($value);
				$value = sprintf(
					'(Closure at %s:%s)',
					$closureReflection->getFileName(),
					$closureReflection->getStartLine()
				);
			}elseif(is_object($value)){
				$value = sprintf('object(%s)', get_class($value));
			}elseif(is_resource($value)){
				$value = sprintf('resource(%s)', get_resource_type($value));
			}
		};
		do{
			$trace = $traceProperty->getValue($this);
			foreach($trace as &$call){
				array_walk_recursive($call['args'], $flatten);
			}
			unset($call);
			$traceProperty->setValue($this, $trace);
		}while($exception = $this->getPrevious());
		$traceProperty->setAccessible(false);
	}
}