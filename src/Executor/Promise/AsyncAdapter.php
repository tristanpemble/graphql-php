<?php

declare(strict_types=1);

namespace GraphQL\Executor\Promise;

use PHPUnit\Framework\Constraint\Callback;
use Throwable;

interface AsyncAdapter extends PromiseAdapter
{
	public function isAsyncIterable($value): bool;

	public function convertAsyncIterable($iterable): AsyncIterator;

	public function next(AsyncIterator $iterator): Promise;

	/**
	 * Creates a Promise
	 *
	 * Expected resolver signature:
	 *     function(callable $next, callable $close, callable $fail)
	 *
	 * @return AsyncIterator
	 *
	 * @api
	 */
	public function createAsyncIterator(callable $resolver): AsyncIterator;

	public function createClosedAsyncIterator($lastValue = null): AsyncIterator;

	public function createFailedAsyncIterator(Throwable $reason): AsyncIterator;

	public function mapAsyncIterator(AsyncIterator $iterator, callable $callback): AsyncIterator;
}
