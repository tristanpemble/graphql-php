<?php

declare(strict_types=1);

namespace GraphQL\Executor\Promise;

use GraphQL\Utils\Utils;

/**
 * Convenience wrapper for promises represented by Stream Adapter
 */
class AsyncIterator
{
	/** @var mixed */
	public $adoptedStream;

	/** @var AsyncAdapter */
	private $adapter;

	/**
	 * Stream constructor.
	 */
	public function __construct($adoptedStream, AsyncAdapter $adapter)
	{
		Utils::invariant(!$adoptedStream instanceof self, 'Expecting stream from adapted system, got ' . self::class);

		$this->adapter = $adapter;
		$this->adoptedStream = $adoptedStream;
	}

	/**
	 * Returns a promise that resolves true when the next value is ready, false when the stream is successfully closed,
	 * or rejects with an error when the stream has failed.
	 *
	 * @return Promise<IteratorResult>
	 */
	public function next(): Promise
	{
		return $this->adapter->next($this);
	}
}
