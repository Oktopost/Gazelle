<?php
namespace Gazelle\Tests;


use Gazelle\Exceptions\GazelleException;
use Gazelle\Exceptions\Response\ClientException\BadRequestException;
use Gazelle\Exceptions\Response\Unexpected\InvalidJSONResponseException;
use Gazelle\Exceptions\Response\Unexpected\MissingJSONFieldException;
use Gazelle\HTTPMethod;
use Gazelle\IResponse;
use Gazelle\Request;
use Gazelle\RequestMetaData;
use Gazelle\RequestParams;
use Gazelle\Response;
use Gazelle\Tests\Support\SequenceConnection;
use PHPUnit\Framework\TestCase;


class RequestTest extends TestCase
{
	private function response(int $code = 200, string $body = '{"value":42}', array $headers = ['X-Test' => 'yes']): Response
	{
		return (new Response(new RequestParams(), new RequestMetaData(0.0, 0.5)))
			->setCode($code)
			->setBody($body)
			->setHeaders($headers);
	}

	/** @dataProvider methodProvider */
	public function testHTTPMethods(string $method, string $expected): void
	{
		$response = $this->response();
		$connection = new SequenceConnection($response);
		$request = new Request($connection);

		self::assertSame($response, $request->{$method}());
		self::assertSame($expected, $connection->requests[0]->getMethod());
	}

	/** @dataProvider tryMethodProvider */
	public function testTryHTTPMethods(string $method, string $expected): void
	{
		$response = $this->response();
		$connection = new SequenceConnection($response);
		$request = new Request($connection);

		self::assertSame($response, $request->{$method}());
		self::assertSame($expected, $connection->requests[0]->getMethod());
	}

	public function testTrySendHandlesGazelleExceptions(): void
	{
		$request = new Request(new SequenceConnection(new GazelleException('failed')));
		self::assertNull($request->trySend());
		self::assertTrue($request->hasError());
		self::assertSame('failed', $request->getLastException()->getMessage());

		$this->expectException(GazelleException::class);
		$request->throwLastException();
	}

	public function testTrySendReturnsResponseFromResponseException(): void
	{
		$response = $this->response(400);
		$request = new Request(new SequenceConnection(new BadRequestException($response)));

		self::assertSame($response, $request->trySend());
		self::assertInstanceOf(BadRequestException::class, $request->getLastException());
	}

	public function testQueryHelpers(): void
	{
		$responses = array_fill(0, 6, $this->response());
		$request = new Request(new SequenceConnection(...$responses));

		self::assertSame(200, $request->queryCode());
		self::assertTrue($request->queryOK());
		self::assertSame(['X-Test' => 'yes'], $request->queryHeaders());
		self::assertSame('{"value":42}', $request->queryBody());
		self::assertSame(['value' => 42], $request->queryJSON());
		self::assertSame(42, $request->queryJSONField('value'));
	}

	public function testTryQueryHelpersAndDefaults(): void
	{
		$failure = new GazelleException('offline');
		$request = new Request(new SequenceConnection($failure, $failure, $failure, $failure));

		self::assertNull($request->tryQueryCode());
		self::assertFalse($request->tryQueryOK());
		self::assertSame([], $request->tryQueryHeaders(true));
		self::assertSame('', $request->tryQueryBody(true));

		$valid = new Request(new SequenceConnection($this->response()));
		self::assertSame(['value' => 42], $valid->tryQueryJSON());

		$missing = new Request(new SequenceConnection($this->response()));
		self::assertSame('fallback', $missing->tryQueryJSONField('missing', 'fallback'));
	}

	public function testMissingJSONFieldThrows(): void
	{
		$request = new Request(new SequenceConnection($this->response()));
		$this->expectException(MissingJSONFieldException::class);
		$request->queryJSONField('missing');
	}

	public function testCloneAndClose(): void
	{
		$first = $this->response(200, 'first');
		$second = $this->response(200, 'second');
		$connection = new SequenceConnection($first, $second);
		$request = new Request($connection);
		$clone = clone $request;

		self::assertSame($first, $clone->send());
		self::assertCount(0, $connection->requests, 'The clone must use its own cloned connection');
		self::assertFalse($clone->hasError());
		$clone->close();
		self::assertSame($first, $request->send());
		self::assertSame($second, $request->send());
		self::assertCount(2, $connection->requests);
	}

	public static function methodProvider(): array
	{
		return [
			['get', HTTPMethod::GET], ['put', HTTPMethod::PUT], ['post', HTTPMethod::POST],
			['head', HTTPMethod::HEAD], ['delete', HTTPMethod::DELETE], ['options', HTTPMethod::OPTIONS],
			['patch', HTTPMethod::PATCH], ['trace', HTTPMethod::TRACE],
		];
	}

	public static function tryMethodProvider(): array
	{
		return [
			['tryGet', HTTPMethod::GET], ['tryPut', HTTPMethod::PUT], ['tryPost', HTTPMethod::POST],
			['tryHead', HTTPMethod::HEAD], ['tryDelete', HTTPMethod::DELETE], ['tryOptions', HTTPMethod::OPTIONS],
			['tryPatch', HTTPMethod::PATCH], ['tryTrace', HTTPMethod::TRACE],
		];
	}
}
