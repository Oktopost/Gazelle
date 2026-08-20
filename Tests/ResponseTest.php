<?php
namespace Gazelle\Tests;


use Gazelle\Exceptions\Response\Unexpected\InvalidJSONResponseException;
use Gazelle\RequestMetaData;
use Gazelle\RequestParams;
use Gazelle\Response;
use PHPUnit\Framework\TestCase;


class ResponseTest extends TestCase
{
	private function response(int $code = 200, string $body = '', array $headers = []): Response
	{
		return (new Response(new RequestParams(), new RequestMetaData(1.0, 2.0)))
			->setCode($code)
			->setBody($body)
			->setHeaders($headers);
	}

	public function testBodyHeadersAndCopy(): void
	{
		$response = $this->response(201, '{"ok":true}', [
			'Content-Type' => 'application/json',
			'X-Multi' => ['first', 'second'],
		]);
		$response->setHeader('X-Test', 'value');

		self::assertSame(201, $response->getCode());
		self::assertSame('value', $response->getHeader('x-test'));
		self::assertSame('first', $response->getHeader('X-Multi'));
		self::assertTrue($response->hasHeader('X-Test'));
		self::assertTrue($response->hasBody());
		self::assertSame(strlen('{"ok":true}'), $response->bodyLength());
		self::assertSame(['ok' => true], $response->getJSON());
		self::assertSame(['ok' => true], $response->tryGetJSON());
		self::assertSame(1.0, $response->requestMetaData()->getStartTime());

		$copy = Response::copy($response);
		self::assertNotSame($response, $copy);
		self::assertSame($response->getCode(), $copy->getCode());
		self::assertSame($response->getHeaders(), $copy->getHeaders());
		self::assertSame($response->getBody(), $copy->getBody());
	}

	public function testBodyCallbackRunsOnce(): void
	{
		$calls = 0;
		$response = $this->response()->setBodyCallback(function () use (&$calls): string {
			$calls++;
			return 'lazy';
		});

		self::assertSame('lazy', $response->getBody());
		self::assertSame('lazy', $response->getBody());
		self::assertSame(1, $calls);
	}

	public function testInvalidJSON(): void
	{
		$response = $this->response(200, 'not-json');
		self::assertNull($response->tryGetJSON());

		$this->expectException(InvalidJSONResponseException::class);
		$response->getJSON();
	}

	/** @dataProvider statusProvider */
	public function testStatusClassification(
		int $code,
		bool $successful,
		bool $complete,
		bool $redirect,
		bool $failed,
		bool $client,
		bool $server
	): void {
		$response = $this->response($code);

		self::assertSame($successful, $response->isSuccessful());
		self::assertSame($complete, $response->isComplete());
		self::assertSame($redirect, $response->isRedirect());
		self::assertSame($failed, $response->isFailed());
		self::assertSame($client, $response->isClientError());
		self::assertSame($server, $response->isServerError());
	}

	public static function statusProvider(): array
	{
		return [
			[204, true, true, false, false, false, false],
			[302, true, false, true, false, false, false],
			[404, false, false, false, true, true, false],
			[503, false, false, false, true, false, true],
		];
	}
}
