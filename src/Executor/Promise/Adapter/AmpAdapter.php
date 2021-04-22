<?php

declare(strict_types=1);

namespace GraphQL\Executor\Promise\Adapter;

use Amp\Emitter;
use Amp\Failure;
use Amp\Iterator;
use Amp\Success;
use GraphQL\Executor\Promise\AsyncAdapter;
use GraphQL\Executor\Promise\AsyncIterator;
use GraphQL\Executor\Promise\IteratorResult;
use GraphQL\Executor\Promise\Promise;
use Throwable;

class AmpAdapter extends AmpPromiseAdapter implements AsyncAdapter
{
	public function isAsyncIterable($value): bool
	{
		return $value instanceof Iterator;
	}

	public function convertAsyncIterable($iterable): AsyncIterator
	{
		return new AsyncIterator($iterable, $this);
	}

	public function next(AsyncIterator $iterator): Promise
	{
		/** @var Iterator $it */
		$it = $iterator->adoptedStream;

		return $this->convertThenable($it->advance())->then(function ($isDone) use ($it) {
			if ($isDone) return new IteratorResult(null, true);
			return new IteratorResult($it->getCurrent(), false);
		});
	}

	public function createAsyncIterator(callable $resolver): AsyncIterator
	{
		$emitter = new Emitter();

		$resolver(
			static function (mixed $value) use ($emitter): Promise {
				return new Promise($emitter->emit($value), $this);
			},
			static function () use ($emitter): void {
				$emitter->complete();
			},
			static function (Throwable $error) use ($emitter): void {
				$emitter->fail($error);
			}
		);

		return new AsyncIterator($emitter->iterate(), $this);
	}

	public function createClosedAsyncIterator(mixed $lastValue = null): AsyncIterator
	{
		return new AsyncIterator(new class($lastValue) implements Iterator {
			private mixed $lastValue;

			public function __construct($lastValue)
			{
				$this->lastValue = $lastValue;
			}

			public function advance(): \Amp\Promise
			{
				return new Success(false);
			}

			public function getCurrent()
			{
				return $this->lastValue;
			}
		}, $this);
	}

	public function createFailedAsyncIterator(Throwable $reason): AsyncIterator
	{
		return new AsyncIterator(new class($reason) implements Iterator {
			private Throwable $reason;

			public function __construct(Throwable $reason)
			{
				$this->reason = $reason;
			}

			public function advance(): \Amp\Promise
			{
				return new Failure($this->reason);
			}

			public function getCurrent()
			{
				return null;
			}
		}, $this);
	}

	public function mapAsyncIterator(AsyncIterator $iterator, callable $callback): AsyncIterator
	{
		return $this->convertAsyncIterable(new class($iterator->adoptedStream, $callback) implements Iterator {
			private Iterator $wrapped;
			/** @var callable */
			private $callback;

			public function __construct(Iterator $wrapped, callable $callback)
			{
				$this->wrapped = $wrapped;
				$this->callback = $callback;
			}

			public function advance(): \Amp\Promise
			{
				return $this->wrapped->advance();
			}

			public function getCurrent()
			{
				return ($this->callback)($this->wrapped->getCurrent());
			}
		});
	}
}
