<?php
namespace Gazelle\Tests;


use Gazelle\Exceptions\GazelleException;
use Gazelle\Connections\MultiCurlConnection;
use Gazelle\Decorators\MultiRequestRetryDecorator;
use Gazelle\Gazelle;
use Gazelle\GazelleMock;
use Gazelle\HTTPMethod;
use Gazelle\Multi\IMultiConnection;
use Gazelle\Multi\IMultiExecutor;
use Gazelle\Multi\MultiRequest;
use Gazelle\Multi\MultiResult;
use Gazelle\RequestMetaData;
use Gazelle\RequestParams;
use Gazelle\Response;
use Gazelle\Tests\Support\QueueMultiConnection;
use Gazelle\Tests\Support\RawHttpSocketServer;
use PHPUnit\Framework\TestCase;


class MultiTest extends TestCase
{
	protected function tearDown(): void
	{
		GazelleMock::reset();
	}

	private function response(int $code = 200): Response
	{
		return (new Response(new RequestParams(), new RequestMetaData(0.0, 1.0)))
			->setCode($code)
			->setHeaders([])
			->setBody('ok');
	}

	public function testRequestCopiesTemplateAndBuildsPaths(): void
	{
		$gazelle = new Gazelle();
		$gazelle->template()->setURL('https://example.test/api/')->setHeader('X-Template', 'yes');
		$multi = $gazelle->multi();

		$relative = $multi->request('/users', ['X-Request' => 'yes']);
		self::assertSame('/api/users', $relative->getPath());
		self::assertSame('yes', $relative->getHeader('X-Template'));
		self::assertSame('yes', $relative->getHeader('X-Request'));

		$absolute = $multi->request('http://other.test/root');
		self::assertSame('other.test', $absolute->getDomain());
		self::assertSame('/root', $absolute->getPath());

		self::assertSame('example.test', $multi->request()->getDomain());
	}

	/** @dataProvider requestMethodProvider */
	public function testMultiRequestMethods(string $method, string $expected): void
	{
		$executor = $this->createMock(IMultiExecutor::class);
		$executor->expects(self::once())
			->method('execute')
			->with(self::callback(fn(MultiRequest $request) => $request->getMethod() === $expected))
			->willReturnCallback(fn(MultiRequest $request) => new MultiResult($request, $executor));

		$request = new MultiRequest($executor);
		self::assertInstanceOf(MultiResult::class, $request->{$method}());
	}

	public function testMultiRequestCannotExecuteTwice(): void
	{
		$executor = $this->createMock(IMultiExecutor::class);
		$executor->method('execute')
			->willReturnCallback(fn(MultiRequest $request) => new MultiResult($request, $executor));
		$request = new MultiRequest($executor);
		$request->send();

		$this->expectException(GazelleException::class);
		$request->send();
	}

	public function testQueueLifecycleAndCompletionCallback(): void
	{
		$connection = new QueueMultiConnection();
		$multi = (new Gazelle())->multi();
		$multi->setConnection($connection);
		$callbackResult = null;

		$result = $multi->request('https://example.test')->get();
		$result->onComplete(function (MultiResult $completed) use (&$callbackResult): void {
			$callbackResult = $completed;
		});

		self::assertTrue($multi->hasPending());
		self::assertTrue($multi->hasAny());
		self::assertFalse($multi->isEmpty());
		self::assertSame(1, $multi->pendingCount());
		self::assertSame(1, $multi->requestsCount());

		$response = $this->response();
		$connection->complete($result, $response);
		self::assertTrue($multi->waitForNext());
		self::assertSame($result, $callbackResult);
		self::assertFalse($multi->hasPending());
		self::assertTrue($multi->hasNext());
		self::assertSame(1, $multi->readyCount());
		self::assertSame($result, $multi->next());
		self::assertSame($response, $result->response());
		self::assertFalse($result->hasError());
		self::assertTrue($result->isExecuted());
		self::assertTrue($multi->isEmpty());

		$immediate = null;
		$result->onComplete(function ($value) use (&$immediate): void {
			$immediate = $value;
		});
		self::assertSame($result, $immediate);
	}

	public function testAbortAndReset(): void
	{
		$connection = new QueueMultiConnection();
		$multi = (new Gazelle())->multi();
		$multi->setConnection($connection);
		$result = $multi->request('https://example.test')->get();

		self::assertTrue($result->abort());
		self::assertFalse($multi->hasPending());
		self::assertFalse($result->abort());

		$result = $multi->request('https://example.test')->get();
		$connection->complete($result, $this->response());
		self::assertFalse($multi->abort($result));

		$multi->reset();
		self::assertTrue($connection->closed);
		self::assertTrue($multi->isEmpty());
	}

	public function testAllNextAndWaitForAllEmptyQueue(): void
	{
		$connection = new QueueMultiConnection();
		$multi = (new Gazelle())->multi();
		$multi->setConnection($connection);

		self::assertTrue($multi->waitForAll());
		self::assertFalse($multi->waitForNext());
		self::assertNull($multi->next(0));
		self::assertSame([], $multi->allNext(0));
	}

	public function testExecuteAllWaitsForPendingCurlRequests(): void
	{
		$server = new RawHttpSocketServer(RawHttpSocketServer::buildResponse(
			200, 'OK', ['Content-Length' => '2'], 'ok'
		));
		$connection = new MultiCurlConnection();
		$multi = (new Gazelle())->multi();
		$multi->setConnection($connection);

		try {
			$result = $multi->request('http://127.0.0.1:' . $server->port() . '/execute-all')
				->setParseResponseForErrors(false)
				->get();

			$multi->executeAll();

			self::assertTrue($result->isExecuted());
			self::assertSame($result, $multi->next(0));
			self::assertSame('ok', $result->response()?->getBody());
		} finally {
			$connection->close();
			$server->close();
		}
	}

	public function testResetClosesConnectionThroughMultiDecoratorChain(): void
	{
		$connection = new QueueMultiConnection();
		GazelleMock::$multiConnection = $connection;
		$multi = (new Gazelle())
			->addMultiDecorators(new MultiRequestRetryDecorator())
			->multi();

		$multi->reset();

		self::assertTrue($connection->closed);
		self::assertTrue($multi->isEmpty());
	}

	public function testResetUsesCloseFromMultiConnectionContract(): void
	{
		$connection = $this->createMock(IMultiConnection::class);
		$connection->expects(self::once())->method('close');
		$multi = (new Gazelle())->multi();
		$multi->setConnection($connection);

		$multi->reset();
	}

	public function testUnknownCompletedResultIsRejected(): void
	{
		$connection = new QueueMultiConnection();
		$multi = (new Gazelle())->multi();
		$multi->setConnection($connection);
		$executor = $this->createMock(IMultiExecutor::class);
		$unknown = new MultiResult(new MultiRequest($executor), $executor);
		$connection->complete($unknown, $this->response());

		$this->expectException(GazelleException::class);
		$multi->waitForNext();
	}

	public function testGazelleMockProvidesDefaultMultiConnection(): void
	{
		$connection = new QueueMultiConnection();
		GazelleMock::$multiConnection = $connection;
		self::assertSame($connection, (new Gazelle())->multi()->getConnection());
		GazelleMock::$connection = $this->createMock(\Gazelle\IConnection::class);
		GazelleMock::reset();
		self::assertNull(GazelleMock::$multiConnection);
		self::assertNull(GazelleMock::$connection);
	}

	public function testMultiResultGuardsDuplicateCompletion(): void
	{
		$executor = $this->createMock(IMultiExecutor::class);
		$result = new MultiResult(new MultiRequest($executor), $executor);
		$error = new \RuntimeException('failed');
		$result->setResult(null, $error);
		self::assertSame($error, $result->error());
		self::assertTrue($result->hasError());

		$this->expectException(GazelleException::class);
		$result->setResult($this->response(), null);
	}

	public static function requestMethodProvider(): array
	{
		return [
			['get', HTTPMethod::GET], ['put', HTTPMethod::PUT], ['post', HTTPMethod::POST],
			['head', HTTPMethod::HEAD], ['delete', HTTPMethod::DELETE], ['options', HTTPMethod::OPTIONS],
			['patch', HTTPMethod::PATCH], ['send', HTTPMethod::GET],
		];
	}
}
