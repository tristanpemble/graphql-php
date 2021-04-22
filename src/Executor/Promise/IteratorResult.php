<?php

declare(strict_types=1);

namespace GraphQL\Executor\Promise;

final class IteratorResult
{
	private bool $isDone;
	private mixed $value;

	/**
	 * @param mixed $value
	 * @param bool  $isDone
	 */
	public function __construct(mixed $value, bool $isDone)
	{
		$this->value = $value;
		$this->isDone = $isDone;
	}

	/**
	 * @return mixed
	 */
	public function value(): mixed
	{
		return $this->value;
	}

	/**
	 * If
	 *
	 * @return bool
	 */
	public function isDone(): bool
	{
		return $this->isDone;
	}
}
