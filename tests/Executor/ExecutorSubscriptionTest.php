<?php

declare(strict_types=1);

namespace GraphQL\Tests\Executor;

use Amp\Delayed;
use Amp\Loop;
use Amp\Producer;
use Amp\Promise;
use GraphQL\Error\DebugFlag;
use GraphQL\Executor\Executor;
use GraphQL\Executor\Promise\Adapter\AmpAdapter;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use function Amp\call;

class ExecutorSubscriptionTest extends TestCase
{
	public function testSomething()
	{
		$subscription = new ObjectType([
			'name' => 'Subscription',
			'fields' => [
				'hello' => [
					'type' => Type::string(),
					'resolve' => function ($rootValue) {
						return $rootValue * 10;
					},
					'subscribe' => function () {
						return new Producer(function ($emit) {
							for ($i = 0 ; $i < 10; $i++) {
								yield $emit($i);
								yield new Delayed(10);
							}
						});
					},
				],
			],
		]);

		$schema = new Schema([
			'subscription' => $subscription,
		]);

		$doc = '
		subscription {
		  hello
		}
		';
		$ast = Parser::parse($doc);

		Loop::run(function() use ($ast, $schema) {
			$adapter = new AmpAdapter();
			/** @var \GraphQL\Executor\Promise\AsyncIterator $result */
			$result = yield Executor::subscribeToExecute($adapter, $schema, $ast)->adoptedPromise;
			/** @var \Amp\Iterator $stream */
			$stream = $result->adoptedStream;

			while (yield $stream->advance()) {
				/** @var \GraphQL\Executor\ExecutionResult $result */
				$result = yield $stream->getCurrent()->adoptedPromise;
				echo json_encode($result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE), JSON_PRETTY_PRINT), PHP_EOL;
			}
		});

		$this->assertTrue(true);
	}
}
