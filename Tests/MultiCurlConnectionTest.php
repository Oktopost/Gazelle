<?php
namespace Gazelle\Tests;


use Gazelle\Connections\MultiCurlConnection;
use Gazelle\Exceptions\Multi\InitMultiRequestException;
use Gazelle\Exceptions\Multi\UnexpectedCurlHandleException;
use Gazelle\Exceptions\Response\ServerException\InternalServerErrorException;
use Gazelle\Multi\IMultiExecutor;
use Gazelle\Multi\MultiRequest;
use Gazelle\Tests\Support\RawHttpSocketServer;
use PHPUnit\Framework\TestCase;


class MultiCurlConnectionTest extends TestCase
{
	private function drain(MultiCurlConnection $connection, int $count): array
	{
		$results = [];
		for ($attempt = 0; $attempt < 100 && count($results) < $count; $attempt++) {
			$result = $connection->next(0.01);
			if ($result) {
				$results[] = $result;
			}
		}

		return $results;
	}

	private function requestResponse(int $status, bool $parseResponseForErrors): \Gazelle\Multi\MultiResult
	{
		$body = 'response';
		$server = new RawHttpSocketServer(RawHttpSocketServer::buildResponse(
			$status, 'Test Response', ['Content-Length' => (string) strlen($body)], $body
		));
		$connection = new MultiCurlConnection();
		$executor = $this->createMock(IMultiExecutor::class);

		try {
			$request = (new MultiRequest($executor))
				->setURL('http://127.0.0.1:' . $server->port() . '/status')
				->setParseResponseForErrors($parseResponseForErrors);
			$result = $connection->send($request, $executor);
			$completed = $this->drain($connection, 1);

			self::assertCount(1, $completed);
			self::assertSame($result, $completed[0]);

			return $result;
		} finally {
			$connection->close();
			$server->close();
		}
	}

	public function testOptionsHandleCreationAndClose(): void
	{
		$connection = new MultiCurlConnection();
		$connection->setOptions([CURLMOPT_MAX_TOTAL_CONNECTIONS => 2]);
		self::assertSame([CURLMOPT_MAX_TOTAL_CONNECTIONS => 2], $connection->getOptions());
		self::assertNull($connection->curlMultiHandle());
		self::assertInstanceOf(\CurlMultiHandle::class, $connection->curlMultiHandle(true));
		$connection->close();
		self::assertNull($connection->curlMultiHandle());
	}

	public function testSendAbortAndUnknownAbort(): void
	{
		$executor = $this->createMock(IMultiExecutor::class);
		$request = (new MultiRequest($executor))->setURL('https://example.test/path');
		$connection = new MultiCurlConnection();
		$result = $connection->send($request, $executor);
		self::assertFalse($result->isExecuted());
		self::assertTrue($connection->abort($result));
		self::assertFalse($connection->abort($result));
		$connection->close();
	}

	public function testMultipleInflightRequestsRemainMatchedToTheirHandles(): void
	{
		$servers = [];
		$expectedBodies = [];
		$connection = new MultiCurlConnection();
		$executor = $this->createMock(IMultiExecutor::class);
		try {
			foreach (['first', 'second', 'third'] as $body) {
				$server = new RawHttpSocketServer(RawHttpSocketServer::buildResponse(
					200, 'OK', ['Content-Length' => (string) strlen($body)], $body
				));
				$servers[] = $server;
				$request = (new MultiRequest($executor))
					->setURL('http://127.0.0.1:' . $server->port() . '/' . $body)
					->setParseResponseForErrors(false);
				$result = $connection->send($request, $executor);
				$expectedBodies[spl_object_id($result)] = $body;
			}

			$results = $this->drain($connection, 3);
			self::assertCount(3, $results);
			foreach ($results as $result) {
				self::assertArrayHasKey(spl_object_id($result), $expectedBodies);
				self::assertFalse($result->hasError());
				$response = $result->response();
				self::assertNotNull($response);
				self::assertSame($expectedBodies[spl_object_id($result)], $response->getBody());
			}
		} finally {
			$connection->close();
			foreach ($servers as $server) {
				$server->close();
			}
		}
	}

	public function testOneFailedInflightRequestDoesNotBreakSuccessfulRequests(): void
	{
		$servers = [];
		$connection = new MultiCurlConnection();
		$executor = $this->createMock(IMultiExecutor::class);
		$expectations = [];
		try {
			foreach (['first', 'second'] as $body) {
				$server = new RawHttpSocketServer(RawHttpSocketServer::buildResponse(
					200, 'OK', ['Content-Length' => (string) strlen($body)], $body
				));
				$servers[] = $server;
				$request = (new MultiRequest($executor))
					->setURL('http://127.0.0.1:' . $server->port() . '/' . $body)
					->setParseResponseForErrors(false);
				$result = $connection->send($request, $executor);
				$expectations[spl_object_id($result)] = $body;
			}

			// Accept the connection and close it without an HTTP response. This produces a
			// deterministic curl transport error without racing for an unbound TCP port.
			$failedServer = new RawHttpSocketServer('');
			$servers[] = $failedServer;
			$failedRequest = (new MultiRequest($executor))
				->setURL('http://127.0.0.1:' . $failedServer->port() . '/failing')
				->setParseResponseForErrors(false);
			$failed = $connection->send($failedRequest, $executor);

			$results = $this->drain($connection, 3);
			self::assertCount(3, $results);
			self::assertTrue($failed->hasError());
			foreach ($results as $result) {
				if ($result === $failed) {
					continue;
				}
				self::assertFalse($result->hasError());
				$response = $result->response();
				self::assertNotNull($response);
				self::assertSame($expectations[spl_object_id($result)], $response->getBody());
			}
		} finally {
			$connection->close();
			foreach ($servers as $server) {
				$server->close();
			}
		}
	}

	public function testHttpServerErrorIsExposedAsErrorWhenResponseErrorParsingIsEnabled(): void
	{
		$result = $this->requestResponse(500, true);

		self::assertTrue($result->hasError());
		self::assertInstanceOf(InternalServerErrorException::class, $result->error());
		self::assertSame(500, $result->error()->response()->getCode());
	}

	public function testHttpServerErrorRemainsResponseWhenResponseErrorParsingIsDisabled(): void
	{
		$result = $this->requestResponse(500, false);

		self::assertFalse($result->hasError());
		self::assertSame(500, $result->response()?->getCode());
	}

	public function testUnexpectedForeignCurlHandleRaisesDedicatedException(): void
	{
		$server = new RawHttpSocketServer(RawHttpSocketServer::buildResponse(
			200, 'OK', ['Content-Length' => '7'], 'foreign'
		));
		$connection = new MultiCurlConnection();
		$multi = $connection->curlMultiHandle(true);
		$foreign = curl_init('http://127.0.0.1:' . $server->port() . '/foreign');
		curl_setopt($foreign, CURLOPT_RETURNTRANSFER, true);
		curl_multi_add_handle($multi, $foreign);

		try {
			for ($attempt = 0; $attempt < 20; $attempt++) {
				try {
					$connection->next(0.01);
				} catch (UnexpectedCurlHandleException $error) {
					self::assertSame($foreign, $error->getCurlHandle());
					return;
				}
			}
			self::fail('Foreign handle was not reported');
		} finally {
			curl_multi_remove_handle($multi, $foreign);
			$connection->close();
			$server->close();
		}
	}

	public function testInitMultiRequestExceptionRetainsCurlCodeAndRequest(): void
	{
		$executor = $this->createMock(IMultiExecutor::class);
		$request = new MultiRequest($executor);
		$error = new InitMultiRequestException(CURLM_BAD_HANDLE, $request);

		self::assertSame(CURLM_BAD_HANDLE, $error->getCurlCode());
		self::assertSame($request, $error->getRequest());
	}
}
