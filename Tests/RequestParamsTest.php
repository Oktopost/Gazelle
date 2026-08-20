<?php
namespace Gazelle\Tests;


use Gazelle\CertificateInfo;
use Gazelle\Exceptions\FatalGazelleException;
use Gazelle\HTTPMethod;
use Gazelle\RequestParams;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Structura\URL;


class RequestParamsTest extends TestCase
{
	public function testDefaultsAndConfiguration(): void
	{
		$request = new RequestParams();

		self::assertSame(HTTPMethod::GET, $request->getMethod());
		self::assertSame(10.0, $request->getConnectionTimeout());
		self::assertSame(10.0, $request->getExecutionTimeout());
		self::assertSame(3, $request->getMaxRedirects());
		self::assertTrue($request->getParseResponseForErrors());
		self::assertTrue($request->hasCurlOptions());

		$request->setConnectionTimeout(8.0)
			->setExecutionTimeout(5.0)
			->setMaxRedirects(7)
			->setParseResponseForErrors(false)
			->setCurlOption(CURLOPT_VERBOSE, true);

		self::assertSame(5.0, $request->getConnectionTimeout());
		self::assertSame(5.0, $request->getExecutionTimeout());
		self::assertSame(7, $request->getMaxRedirects());
		self::assertFalse($request->getParseResponseForErrors());
		self::assertTrue($request->getCurlOptions()[CURLOPT_VERBOSE]);
		$request->setCurlOptions([]);

		$request->clearCurlInfoOptions();
		$request->setCurlInfoOptions([CURLINFO_HTTP_CODE, CURLINFO_TOTAL_TIME, CURLINFO_HTTP_CODE]);
		self::assertSame([CURLINFO_HTTP_CODE, CURLINFO_TOTAL_TIME], array_values($request->getCurlInfoOptions()));
	}

	#[DataProvider('connectionReuseProvider')]
	public function testConnectionReuseGetterMatchesItsPublicMeaning(?bool $reuse, bool $expected): void
	{
		$request = new RequestParams();
		if ($reuse !== null) {
			$request->setIsConnectionReused($reuse);
		}

		self::assertSame($expected, $request->getIsConnectionReused());
	}

	public static function connectionReuseProvider(): array
	{
		return [
			'default permits reuse' => [null, true],
			'explicitly disabled' => [false, false],
			'explicitly enabled' => [true, true],
		];
	}

	public function testURLPathsQueryAndTags(): void
	{
		$request = new RequestParams();
		$request->setURL('https://example.test:8443/api/')
			->addPath('/users')
			->setQueryParam('q', 'hello world')
			->setQueryParams(['page' => '2'])
			->addTags(['trace' => 42]);

		self::assertSame('https', $request->getScheme());
		self::assertSame('example.test', $request->getDomain());
		self::assertSame('/api/users', $request->getPath());
		self::assertSame('hello+world', $request->getQueryParam('q'));
		self::assertSame('2', $request->getQueryParam('page'));
		self::assertSame(42, $request->getTag('trace'));
		self::assertNull($request->getTag('missing'));
		self::assertStringContainsString('q=hello+world', $request->getURLString());
		self::assertNotSame('', $request->getQueryString());

		$request->setPath('/root')->addPath('child')->addPath('/leaf');
		self::assertSame('/rootchild/leaf', $request->getPath());

		$url = new URL('http://other.test/base');
		$request->setURL($url)->setPort(8080)->setScheme('https')->setDomain('changed.test');
		self::assertSame($url, $request->getURL());
		self::assertStringContainsString('changed.test:8080', $request->getURLString());

		$request->resetParams();
		self::assertSame([], $request->getHeaders());
		self::assertSame([], $request->getBodyParams());
		self::assertSame(HTTPMethod::GET, $request->getMethod());
	}

	public function testHeadersAndBodies(): void
	{
		$request = new RequestParams();
		$request->setHeader('X-One', 'one')
			->setHeader('X-Two:two')
			->setHeader('X-Empty')
			->setHeaders(['X-Multi' => ['a']])
			->setHeaders(['X-Multi' => 'b'], true);

		self::assertSame('one', $request->getHeader('X-One'));
		self::assertSame('two', $request->getHeader('X-Two'));
		self::assertSame('', $request->getHeader('X-Empty'));
		self::assertSame(['a', 'b'], $request->getHeader('X-Multi'));

		$request->removeHeader('X-One');
		self::assertNull($request->getHeader('X-One'));

		$request->setBodyParams(['a' => 'b'], false);
		self::assertSame(['a' => 'b'], $request->getBodyParams());
		self::assertSame('a=b', $request->getBody());

		$request->setBody('plain');
		self::assertSame('plain', $request->getBody());
		self::assertSame([], $request->getBodyParams());

		$request->setBody(['ok' => true]);
		self::assertSame(['ok' => true], jsondecode_a($request->getBody()));

		$request->setBody((object)['value' => 1]);
		self::assertSame(['value' => 1], jsondecode_a($request->getBody()));

		$certificate = CertificateInfo::parse(['name' => 'example.test']);
		$request->setBody($certificate);
		self::assertStringContainsString('example.test', $request->getBody());

		$request->setJsonBody(['json' => true]);
		self::assertSame(['json' => true], jsondecode_a($request->getBody()));

		$request->setBody(null);
		self::assertNull($request->getBody());
	}

	public function testInvalidBodyIsRejected(): void
	{
		$this->expectException(FatalGazelleException::class);
		(new RequestParams())->setBody(fopen('php://memory', 'r'));
	}

	public function testCopyAndCloneAreIndependent(): void
	{
		$source = (new RequestParams())
			->setURL('https://example.test/original')
			->setHeader('X-Test', 'yes')
			->setMethod(HTTPMethod::POST)
			->addTags(['id' => 1]);

		$copy = RequestParams::makeCopy($source);
		$clone = clone $source;
		$copy->setPath('/copy');
		$clone->setDomain('clone.test');

		self::assertSame('/original', $source->getPath());
		self::assertSame('example.test', $source->getDomain());
		self::assertSame('yes', $copy->getHeader('X-Test'));
		self::assertSame(HTTPMethod::POST, $copy->getMethod());
		self::assertSame(['id' => 1], $copy->getTags());
	}

	public function testRemainingConfigurationHelpers(): void
	{
		$request = (new RequestParams())
			->setExecutionTimeout(9.0, 4.0)
			->addPath('/root', false)
			->setQueryParam('q', 'value', false)
			->setBodyParam('first', 'one two')
			->setBodyParams(['second' => 'three four'])
			->basicAuth('user', 'password')
			->setUserAgent('Gazelle Test');

		self::assertSame(4.0, $request->getConnectionTimeout());
		self::assertSame(['q' => 'value'], $request->getQueryParams());
		self::assertArrayHasKey('first', $request->getBodyParams());
		self::assertArrayHasKey('second', $request->getBodyParams());
		self::assertSame('application/x-www-form-urlencoded', $request->getHeader('Content-Type'));
		self::assertSame('user:password', $request->getCurlOptions()[CURLOPT_USERPWD]);
		self::assertSame('Gazelle Test', $request->getCurlOptions()[CURLOPT_USERAGENT]);
	}
}
