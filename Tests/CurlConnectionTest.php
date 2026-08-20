<?php
namespace Gazelle\Tests;


use Gazelle\Connections\CurlConnection;
use Gazelle\Connections\CurlParser;
use Gazelle\Exceptions\GazelleException;
use Gazelle\RequestParams;
use Gazelle\Tests\Support\RawHttpSocketServer;
use PHPUnit\Framework\TestCase;


class CurlConnectionTest extends TestCase
{
	public function testValidatesMalformedRequest(): void
	{
		$this->expectException(GazelleException::class);
		CurlParser::validate(new RequestParams());
	}

	public function testCurlParserCreatesAndReusesHandle(): void
	{
		$request = (new RequestParams())->setURL('https://example.test/path');
		$curl = CurlParser::request(null, $request);
		self::assertInstanceOf(\CurlHandle::class, $curl);
		self::assertSame($curl, CurlParser::request($curl, $request));
	}

	public function testCurlConnectionParsesRealHttpResponseViaRawSocket(): void
	{
		$server = new RawHttpSocketServer(RawHttpSocketServer::buildResponse(
			200, 'OK', ['X-Test' => 'value', 'Content-Length' => '5'], 'hello'
		));

		try {
			$request = (new RequestParams())
				->setURL('http://127.0.0.1:' . $server->port() . '/path')
				->setParseResponseForErrors(false);

			$response = (new CurlConnection())->request($request);

			self::assertSame(200, $response->getCode());
			self::assertSame('hello', $response->getBody());
			self::assertSame('value', $response->getHeader('X-Test'));
		} finally {
			$server->close();
		}
	}
}
