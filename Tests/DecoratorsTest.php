<?php
namespace Gazelle\Tests;


use Gazelle\Decorators\AbstractChainDecorator;
use Gazelle\Decorators\AbstractMaskedRequestDecorator;
use Gazelle\Decorators\GetRequestRetryDecorator;
use Gazelle\Decorators\IPCacheDecorator;
use Gazelle\Decorators\MultiRequestDelayedRetryDecorator;
use Gazelle\Decorators\MultiRequestRetryDecorator;
use Gazelle\Decorators\RequestRetryDecorator;
use Gazelle\Exceptions\GazelleException;
use Gazelle\Exceptions\RequestException;
use Gazelle\Exceptions\Response\ServerException\InternalServerErrorException;
use Gazelle\HTTPMethod;
use Gazelle\IRequestParams;
use Gazelle\IResponse;
use Gazelle\Multi\IMultiExecutor;
use Gazelle\Multi\MultiRequest;
use Gazelle\RequestMetaData;
use Gazelle\RequestParams;
use Gazelle\Response;
use Gazelle\Tests\Support\SequenceConnection;
use Gazelle\Tests\Support\StaticIPProvider;
use Gazelle\Tests\Support\QueueMultiConnection;
use PHPUnit\Framework\TestCase;


class DecoratorsTest extends TestCase
{
	private function response(int $code = 200, ?RequestParams $request = null): Response
	{
		return (new Response($request ?? new RequestParams(), new RequestMetaData(0.0, 1.0)))
			->setCode($code)
			->setBody('ok')
			->setHeaders(['Authorization' => 'secret']);
	}

	private function delayedRetryDecoratorWithoutSleep(int $maxRetries): MultiRequestDelayedRetryDecorator
	{
		return new class($maxRetries) extends MultiRequestDelayedRetryDecorator {
			protected function sleepBeforeRetry(int $seconds): void {}
		};
	}

	public function testRetryRetriesServerFailureAndReturnsSuccess(): void
	{
		$connection = new SequenceConnection(
			$this->response(500), $this->response(502), $this->response(504), $this->response(200)
		);
		$decorator = new RequestRetryDecorator(3);
		$decorator->setChild($connection);

		self::assertSame(200, $decorator->request(new RequestParams())->getCode());
		self::assertCount(4, $connection->requests, 'Three retries mean four total attempts');
	}

	public function testGetRetryDoesNotRetryPost(): void
	{
		$response = $this->response(500);
		$connection = new SequenceConnection($response);
		$decorator = new GetRequestRetryDecorator(3);
		$decorator->setChild($connection);
		$request = (new RequestParams())->setMethod(HTTPMethod::POST);

		self::assertSame($response, $decorator->request($request));
		self::assertCount(1, $connection->requests);
	}

	public function testGetRetryRetriesGetRequest(): void
	{
		$connection = new SequenceConnection($this->response(500), $this->response(200));
		$decorator = new GetRequestRetryDecorator(1);
		$decorator->setChild($connection);
		$request = (new RequestParams())->setMethod(HTTPMethod::GET);

		self::assertSame(200, $decorator->request($request)->getCode());
		self::assertCount(2, $connection->requests, 'One retry means two total attempts');
	}

	public function testRequestRetryReturnsFinalFailureAfterRetryBudget(): void
	{
		$connection = new SequenceConnection($this->response(500), $this->response(502), $this->response(503));
		$decorator = new RequestRetryDecorator(2);
		$decorator->setChild($connection);

		self::assertSame(503, $decorator->request(new RequestParams())->getCode());
		self::assertCount(3, $connection->requests, 'Two retries mean three total attempts');
	}

	public function testRetryFactories(): void
	{
		self::assertInstanceOf(RequestRetryDecorator::class, RequestRetryDecorator::withDelay(1, 0));
		self::assertInstanceOf(RequestRetryDecorator::class, RequestRetryDecorator::withDelays([]));
		self::assertInstanceOf(RequestRetryDecorator::class, RequestRetryDecorator::withDifferentDelays([]));
	}

	public function testChainHooksRunAroundRequest(): void
	{
		$response = $this->response();
		$connection = new SequenceConnection($response);
		$decorator = new class extends AbstractChainDecorator {
			public array $events = [];
			protected function before(IRequestParams $requestParams): void { $this->events[] = 'before'; }
			protected function after(IResponse $requestParams): void { $this->events[] = 'after'; }
			protected function finally(IRequestParams $requestParams, ?IResponse $responseData, ?\Throwable $t): void { $this->events[] = 'finally'; }
		};
		$decorator->setChild($connection);

		self::assertSame($response, $decorator->request(new RequestParams()));
		self::assertSame(['before', 'after', 'finally'], $decorator->events);
	}

	public function testChainHooksRunForErrors(): void
	{
		$error = new \RuntimeException('failed');
		$decorator = new class extends AbstractChainDecorator {
			public array $events = [];
			protected function onError(IRequestParams $requestParams, \Throwable $t): void { $this->events[] = $t->getMessage(); }
			protected function finally(IRequestParams $requestParams, ?IResponse $responseData, ?\Throwable $t): void { $this->events[] = 'finally'; }
		};
		$decorator->setChild(new SequenceConnection($error));

		try {
			$decorator->request(new RequestParams());
			self::fail('A null response must violate the return type');
		} catch (\TypeError) {
			self::assertSame(['failed', 'finally'], $decorator->events);
		}
	}

	public function testIPCacheAddsResolveOnlyForMatchingHost(): void
	{
		$response = $this->response();
		$connection = new SequenceConnection($response, $response);
		$decorator = new IPCacheDecorator('example.test', new StaticIPProvider(['127.0.0.1']));
		$decorator->setChild($connection);

		$matching = (new RequestParams())->setURL('https://example.test/path');
		$decorator->request($matching);
		self::assertSame(['example.test:443:127.0.0.1'], $connection->requests[0]->getCurlOptions()[CURLOPT_RESOLVE]);
		self::assertArrayNotHasKey(CURLOPT_RESOLVE, $matching->getCurlOptions());

		$decorator->request((new RequestParams())->setURL('https://other.test/path'));
		self::assertArrayNotHasKey(CURLOPT_RESOLVE, $connection->requests[1]->getCurlOptions());
	}

	public function testMaskedDecoratorPassesMaskedCopiesToHook(): void
	{
		$request = (new RequestParams())
			->setURL('https://example.test')
			->setHeader('Authorization', 'secret')
			->setQueryParam('token', 'secret', false);
		$response = $this->response(200, $request);
		$connection = new SequenceConnection($response);
		$decorator = new class extends AbstractMaskedRequestDecorator {
			public ?IRequestParams $seenRequest = null;
			public ?IResponse $seenResponse = null;
			protected function getMaskedHeaders(): array { return ['Authorization']; }
			protected function getMaskedQueryParams(): array { return ['token']; }
			protected function onSuccess(IRequestParams $maskedRequest, IResponse $response): void { $this->seenRequest = $maskedRequest; $this->seenResponse = $response; }
			protected function onError(?IRequestParams $request, ?IResponse $response, \Throwable $t): void {}
		};
		$decorator->setChild($connection);

		self::assertSame($response, $decorator->request($request));
		self::assertSame('--masked--', $decorator->seenRequest->getHeader('Authorization'));
		self::assertSame('--masked--', $decorator->seenRequest->getQueryParam('token'));
		self::assertSame('--masked--', $decorator->seenResponse->getHeader('Authorization'));
		self::assertSame('secret', $request->getHeader('Authorization'));
	}

	public function testMaskedDecoratorReportsAndRethrowsGazelleError(): void
	{
		$error = new GazelleException('failed');
		$decorator = new class extends AbstractMaskedRequestDecorator {
			public ?\Throwable $seenError = null;
			protected function onSuccess(IRequestParams $maskedRequest, IResponse $response): void {}
			protected function onError(?IRequestParams $request, ?IResponse $response, \Throwable $t): void { $this->seenError = $t; }
		};
		$decorator->setChild(new SequenceConnection($error));

		try {
			$decorator->request((new RequestParams())->setURL('https://example.test'));
			self::fail('The original exception must be rethrown');
		} catch (GazelleException $caught) {
			self::assertSame($error, $caught);
			self::assertSame($error, $decorator->seenError);
		}
	}

	public function testDecoratorWithoutChildFailsClearly(): void
	{
		$decorator = new RequestRetryDecorator();
		$this->expectException(\Error::class);
		$decorator->request(new RequestParams());
	}

	public function testMultiRetryResubmitsRequestExceptionResult(): void
	{
		$child = new QueueMultiConnection();
		$decorator = new MultiRequestRetryDecorator(1);
		$decorator->setNextConnection($child);
		$executor = $this->createMock(IMultiExecutor::class);
		$request = new MultiRequest($executor);
		$result = $decorator->send($request, $executor);
		$child->complete($result, null, new RequestException($request, 'failed'));

		self::assertNull($decorator->next(0));
		self::assertSame(1, $child->sendUsingCalls);
		self::assertFalse($result->isExecuted());
		self::assertFalse($decorator->abort(new \Gazelle\Multi\MultiResult(new MultiRequest($executor), $executor)));
	}

	public function testMultiRetryResubmitsServerResponseExceptionResult(): void
	{
		$child = new QueueMultiConnection();
		$decorator = new MultiRequestRetryDecorator(1);
		$decorator->setNextConnection($child);
		$executor = $this->createMock(IMultiExecutor::class);
		$request = new MultiRequest($executor);
		$result = $decorator->send($request, $executor);
		$response = $this->response(500, $request);
		$child->complete($result, $response, new InternalServerErrorException($response));

		self::assertNull($decorator->next(0));
		self::assertSame(1, $child->sendUsingCalls);
		self::assertFalse($result->isExecuted());
	}

	public function testMultiRetryPassesSuccessfulResult(): void
	{
		$child = new QueueMultiConnection();
		$decorator = new MultiRequestRetryDecorator();
		$decorator->setNextConnection($child);
		$executor = $this->createMock(IMultiExecutor::class);
		$result = $decorator->send(new MultiRequest($executor), $executor);
		$child->complete($result, $this->response());
		self::assertSame($result, $decorator->next(0));
		self::assertNull($decorator->next(0));
	}

	public function testMultiRetryPassesUntrackedRequestExceptionResult(): void
	{
		$child = new QueueMultiConnection();
		$decorator = new MultiRequestRetryDecorator(1);
		$decorator->setNextConnection($child);
		$executor = $this->createMock(IMultiExecutor::class);
		$request = new MultiRequest($executor);
		$result = new \Gazelle\Multi\MultiResult($request, $executor);
		$error = new RequestException($request, 'untracked failure');
		$child->complete($result, null, $error);

		self::assertSame($result, $decorator->next(0));
		self::assertSame($error, $result->error());
		self::assertSame(0, $child->sendUsingCalls);
	}

	public function testMultiRetryStopsAfterConfiguredNumberOfRetries(): void
	{
		$child = new QueueMultiConnection();
		$decorator = new MultiRequestRetryDecorator(2);
		$decorator->setNextConnection($child);
		$executor = $this->createMock(IMultiExecutor::class);
		$request = new MultiRequest($executor);
		$result = $decorator->send($request, $executor);
		$error = new RequestException($request, 'failed');

		$child->complete($result, null, $error);
		self::assertNull($decorator->next(0));
		$child->complete($result, null, $error);
		self::assertNull($decorator->next(0));
		$child->complete($result, null, $error);
		$terminal = $decorator->next(0);

		self::assertSame(2, $child->sendUsingCalls, 'Only two retries are allowed after the initial attempt');
		self::assertSame($result, $terminal);
		self::assertSame($error, $terminal->error());
	}

	public function testMultiRetryWithZeroRetriesReturnsFirstFailure(): void
	{
		$child = new QueueMultiConnection();
		$decorator = new MultiRequestRetryDecorator(0);
		$decorator->setNextConnection($child);
		$executor = $this->createMock(IMultiExecutor::class);
		$request = new MultiRequest($executor);
		$result = $decorator->send($request, $executor);
		$child->complete($result, null, new RequestException($request, 'failed'));

		self::assertSame($result, $decorator->next(0));
		self::assertSame(0, $child->sendUsingCalls);
	}

	public function testDelayedRetryPassesSuccessfulResult(): void
	{
		$child = new QueueMultiConnection();
		$decorator = new MultiRequestDelayedRetryDecorator(1);
		$decorator->setNextConnection($child);
		$executor = $this->createMock(IMultiExecutor::class);
		$result = $decorator->send(new MultiRequest($executor), $executor);
		$child->complete($result, $this->response());
		self::assertSame($result, $decorator->next(0));
	}

	public function testDelayedRetryDoesNotRetainSubmittedRequestsInParentState(): void
	{
		$child = new QueueMultiConnection();
		$decorator = new MultiRequestDelayedRetryDecorator(1);
		$decorator->setNextConnection($child);
		$executor = $this->createMock(IMultiExecutor::class);

		$decorator->send(new MultiRequest($executor), $executor);
		$decorator->send(new MultiRequest($executor), $executor);

		$allRequests = new \ReflectionProperty(MultiRequestRetryDecorator::class, 'allRequests');
		self::assertSame([], $allRequests->getValue($decorator));
	}

	public function testDelayedRetryBatchesFailuresAndStopsAfterRetryBudget(): void
	{
		$child = new QueueMultiConnection();
		$decorator = $this->delayedRetryDecoratorWithoutSleep(1);
		$decorator->setNextConnection($child);
		$executor = $this->createMock(IMultiExecutor::class);
		$firstRequest = new MultiRequest($executor);
		$secondRequest = new MultiRequest($executor);
		$first = $decorator->send($firstRequest, $executor);
		$second = $decorator->send($secondRequest, $executor);

		$child->complete($first, null, new RequestException($firstRequest, 'first failed'));
		$child->complete($second, null, new RequestException($secondRequest, 'second failed'));
		self::assertNull($decorator->next(0));
		self::assertSame(0, $child->sendUsingCalls, 'The batch must wait for every in-flight request');
		self::assertNull($decorator->next(0));
		self::assertSame(2, $child->sendUsingCalls, 'Both failures must be retried as one batch');

		$terminalError = new RequestException($firstRequest, 'retry failed');
		$child->complete($first, null, $terminalError);
		self::assertSame($first, $decorator->next(0), 'The configured retry budget is exhausted');
		self::assertSame($terminalError, $first->error());
	}

	public function testDelayedRetryUsesSecondRetryRoundBeforeExhaustingBudget(): void
	{
		$child = new QueueMultiConnection();
		$decorator = $this->delayedRetryDecoratorWithoutSleep(2);
		$decorator->setNextConnection($child);
		$executor = $this->createMock(IMultiExecutor::class);
		$request = new MultiRequest($executor);
		$result = $decorator->send($request, $executor);
		$error = new RequestException($request, 'failed');

		$child->complete($result, null, $error);
		self::assertNull($decorator->next(0));
		self::assertSame(1, $child->sendUsingCalls);

		$child->complete($result, null, $error);
		self::assertNull($decorator->next(0));
		self::assertSame(2, $child->sendUsingCalls);

		$child->complete($result, null, $error);
		self::assertSame($result, $decorator->next(0));
		self::assertSame($error, $result->error());
	}

	public function testDelayedRetryBudgetIsResetForNextIndependentBatch(): void
	{
		$child = new QueueMultiConnection();
		$decorator = $this->delayedRetryDecoratorWithoutSleep(1);
		$decorator->setNextConnection($child);
		$executor = $this->createMock(IMultiExecutor::class);

		foreach (['first', 'second'] as $message)
		{
			$request = new MultiRequest($executor);
			$result = $decorator->send($request, $executor);
			$error = new RequestException($request, $message);

			$child->complete($result, null, $error);
			self::assertNull($decorator->next(0));

			$child->complete($result, null, $error);
			self::assertSame($result, $decorator->next(0));
		}

		self::assertSame(2, $child->sendUsingCalls, 'Each independent batch must receive its own retry budget');
	}

	public function testDelayedRetryWithZeroRetriesReturnsFirstFailure(): void
	{
		$child = new QueueMultiConnection();
		$decorator = $this->delayedRetryDecoratorWithoutSleep(0);
		$decorator->setNextConnection($child);
		$executor = $this->createMock(IMultiExecutor::class);
		$request = new MultiRequest($executor);
		$result = $decorator->send($request, $executor);
		$error = new RequestException($request, 'failed');
		$child->complete($result, null, $error);

		self::assertSame($result, $decorator->next(0));
		self::assertSame($error, $result->error());
		self::assertSame(0, $child->sendUsingCalls);
	}

	public function testDelayedRetryStartsBatchAfterMixedSuccessAndFailure(): void
	{
		$child = new QueueMultiConnection();
		$decorator = $this->delayedRetryDecoratorWithoutSleep(1);
		$decorator->setNextConnection($child);
		$executor = $this->createMock(IMultiExecutor::class);
		$successfulRequest = new MultiRequest($executor);
		$failedRequest = new MultiRequest($executor);
		$successful = $decorator->send($successfulRequest, $executor);
		$failed = $decorator->send($failedRequest, $executor);

		$child->complete($successful, $this->response());
		$child->complete($failed, null, new RequestException($failedRequest, 'failed'));
		self::assertSame($successful, $decorator->next(0));
		self::assertNull($decorator->next(0));
		self::assertSame(1, $child->sendUsingCalls, 'The failed request must be retried after all results are processed');
	}

	public function testDelayedRetryStartsQueuedRetryAfterOtherInflightRequestIsAborted(): void
	{
		$child = new QueueMultiConnection();
		$decorator = $this->delayedRetryDecoratorWithoutSleep(1);
		$decorator->setNextConnection($child);
		$executor = $this->createMock(IMultiExecutor::class);
		$failedRequest = new MultiRequest($executor);
		$failed = $decorator->send($failedRequest, $executor);
		$pending = $decorator->send(new MultiRequest($executor), $executor);

		$child->complete($failed, null, new RequestException($failedRequest, 'failed'));
		self::assertNull($decorator->next(0));
		self::assertSame(0, $child->sendUsingCalls);

		self::assertTrue($decorator->abort($pending));
		$decorator->next(0);

		self::assertSame(1, $child->sendUsingCalls, 'Aborting the last in-flight request must release the queued retry batch');
		self::assertFalse($failed->isExecuted());
	}

	public function testMaskedMultiLifecycle(): void
	{
		$child = new QueueMultiConnection();
		$decorator = new class extends AbstractMaskedRequestDecorator {
			public int $successes = 0;
			public array $aborts = [];
			protected function onSuccess(IRequestParams $maskedRequest, IResponse $response): void { $this->successes++; }
			protected function onError(?IRequestParams $request, ?IResponse $response, \Throwable $t): void {}
			protected function onAborted(bool $isAborted, ?IRequestParams $request, ?IResponse $response): void { $this->aborts[] = $isAborted; }
		};
		$decorator->setNextConnection($child);
		$executor = $this->createMock(IMultiExecutor::class);
		$request = new MultiRequest($executor);
		$result = $decorator->send($request, $executor);
		$child->complete($result, $this->response(200, $request));
		self::assertSame($result, $decorator->next(0));
		self::assertSame(1, $decorator->successes);

		$pending = $decorator->send(new MultiRequest($executor), $executor);
		self::assertTrue($decorator->abort($pending));
		self::assertSame([true], $decorator->aborts);
		$decorator->sendUsing($result);
		self::assertSame(1, $child->sendUsingCalls);
		$decorator->close();
		self::assertTrue($child->closed);
	}

	public function testIPCacheFactoryUsesProvidedBase(): void
	{
		$decorator = IPCacheDecorator::createFromDNSResolve(
			new StaticIPProvider(['127.0.0.2']), 'example.test', 'coverage-cache', 60, sys_get_temp_dir()
		);
		$connection = new SequenceConnection($this->response());
		$decorator->setChild($connection);
		$decorator->request((new RequestParams())->setURL('https://example.test/path'));
		self::assertSame(['example.test:443:127.0.0.2'], $connection->requests[0]->getCurlOptions()[CURLOPT_RESOLVE]);
	}
}
