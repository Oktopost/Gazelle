<?php
namespace Gazelle\Tests;


use Gazelle\HTTPMethod;
use Gazelle\RequestParams;
use Gazelle\Utils\HeadersParser;
use Gazelle\Utils\OptionsConfig;
use PHPUnit\Framework\TestCase;


class UtilsTest extends TestCase
{
	public function testHeaderParsing(): void
	{
		$raw = "HTTP/1.1 301 Moved\r\nLocation: /next\r\nX-Test: one\r\n\r\n"
			. "HTTP/1.1 200 OK\r\nX-Test: two\r\nX-Test: three\r\nEmpty:\r\n\r\n";

		self::assertCount(2, HeadersParser::getRequestHeaders($raw));
		$all = HeadersParser::parseAllHeaders($raw, true);
		self::assertSame('/next', $all[0]['Location']);
		self::assertSame(['two', 'three'], $all[1]['X-Test']);
		self::assertSame('', $all[1]['Empty']);
		self::assertSame('three', HeadersParser::parseLastRequestHeaders($raw)['X-Test']);

		$single = HeadersParser::parseSingleRequestHeaders("Key: value\nFlag");
		self::assertSame(['Key' => 'value', 'Flag' => ''], $single);
	}

	/** @dataProvider redirectProvider */
	public function testOptionsGeneration(int $redirects, bool $follow, ?int $expected): void
	{
		$request = (new RequestParams())
			->setURL('https://example.test/path')
			->setMaxRedirects($redirects)
			->setConnectionTimeout(0.2)
			->setExecutionTimeout(0.5)
			->setHeader('X-Test', 'one')
			->setHeaders(['X-Multi' => ['a', 'b']])
			->setBody('payload')
			->setMethod(HTTPMethod::HEAD);

		$options = OptionsConfig::generate($request);
		self::assertSame($follow, $options[CURLOPT_FOLLOWLOCATION]);
		self::assertSame($expected, $options[CURLOPT_MAXREDIRS]);
		self::assertSame(200.0, $options[CURLOPT_CONNECTTIMEOUT_MS]);
		self::assertSame(500.0, $options[CURLOPT_TIMEOUT_MS]);
		self::assertSame('https://example.test/path', $options[CURLOPT_URL]);
		self::assertSame('payload', $options[CURLOPT_POSTFIELDS]);
		self::assertSame(HTTPMethod::HEAD, $options[CURLOPT_CUSTOMREQUEST]);
		self::assertTrue($options[CURLOPT_NOBODY]);
		self::assertContains('X-Test: one', $options[CURLOPT_HTTPHEADER]);
		self::assertContains('X-Multi: b', $options[CURLOPT_HTTPHEADER]);
	}

	public function testGetOptionsDoNotAddEmptyBodyOrCustomMethod(): void
	{
		$options = OptionsConfig::generate((new RequestParams())->setURL('http://example.test'));
		self::assertArrayNotHasKey(CURLOPT_POSTFIELDS, $options);
		self::assertArrayNotHasKey(CURLOPT_CUSTOMREQUEST, $options);
		self::assertArrayNotHasKey(CURLOPT_HTTPHEADER, $options);
	}

	public static function redirectProvider(): array
	{
		return [[0, false, 0], [-1, true, null], [3, true, 3]];
	}
}
