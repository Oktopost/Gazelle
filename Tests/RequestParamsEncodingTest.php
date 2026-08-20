<?php
namespace Gazelle\Tests;


use Gazelle\RequestParams;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;


class RequestParamsEncodingTest extends TestCase
{
	#[DataProvider('scalarQueryProvider')]
	public function testScalarQueryParameterIsEncodedExactlyOnce(string $input, string $encoded): void
	{
		$request = (new RequestParams())
			->setURL('https://example.test/search')
			->setQueryParam('value', $input);

		self::assertSame($encoded, $request->getQueryParam('value'));
		self::assertSame('https://example.test/search?value=' . $encoded, $request->getURLString());
	}

	public function testArrayQueryParameterEncodesEveryValueAndUsesRepeatedArrayKeys(): void
	{
		$request = (new RequestParams())
			->setURL('https://example.test/search')
			->setQueryParam('values', ['one two', 'a+b', 'slash/value']);

		self::assertSame(['one+two', 'a%2Bb', 'slash%2Fvalue'], $request->getQueryParam('values'));
		self::assertSame(
			'https://example.test/search?values[]=one+two&values[]=a%2Bb&values[]=slash%2Fvalue',
			$request->getURLString()
		);
	}

	public function testMixedQueryParametersEncodeScalarAndArrayValues(): void
	{
		$request = (new RequestParams())
			->setURL('https://example.test/search')
			->setQueryParams([
				'query' => 'one two',
				'filters' => ['first value', 'a+b'],
			]);

		self::assertSame([
			'query' => 'one+two',
			'filters' => ['first+value', 'a%2Bb'],
		], $request->getQueryParams());
		self::assertSame(
			'https://example.test/search?query=one+two&filters[]=first+value&filters[]=a%2Bb',
			$request->getURLString()
		);
	}

	public function testQueryEscapeFalsePreservesScalarAndArrayValues(): void
	{
		$request = (new RequestParams())->setURL('https://example.test/search');
		$request->setQueryParams([
			'query' => 'already+encoded',
			'filters' => ['first%20value', 'a%2Bb'],
		], false);

		self::assertSame([
			'query' => 'already+encoded',
			'filters' => ['first%20value', 'a%2Bb'],
		], $request->getQueryParams());
		self::assertSame(
			'https://example.test/search?query=already+encoded&filters[]=first%20value&filters[]=a%2Bb',
			$request->getURLString()
		);
	}

	public function testSingleArrayQueryParameterSupportsEscapeFalse(): void
	{
		$request = (new RequestParams())->setURL('https://example.test/search');
		$request->setQueryParam('filters', ['first%20value', 'a%2Bb'], false);

		self::assertSame(['first%20value', 'a%2Bb'], $request->getQueryParam('filters'));
		self::assertSame(
			'https://example.test/search?filters[]=first%20value&filters[]=a%2Bb',
			$request->getURLString()
		);
	}

	#[DataProvider('bodyProvider')]
	public function testSingleBodyParameterIsEncodedExactlyOnce(string $input, string $expectedBody): void
	{
		$request = (new RequestParams())->setBodyParam('value', $input);

		self::assertSame($expectedBody, $request->getBody());
		parse_str($request->getBody(), $decoded);
		self::assertSame($input, $decoded['value']);
	}

	public function testBodyParametersRoundTripScalarAndArrayValues(): void
	{
		$input = [
			'name' => 'one two',
			'symbols' => 'a+b & c/%',
			'ids' => ['first value', 'a+b', '10'],
		];
		$request = (new RequestParams())->setBodyParams($input);

		parse_str($request->getBody(), $decoded);
		self::assertSame($input, $decoded);
		self::assertSame('application/x-www-form-urlencoded', $request->getHeader('Content-Type'));
	}

	public function testSetBodyParamSupportsAnArrayValue(): void
	{
		$request = (new RequestParams())->setBodyParam('ids', ['10', '20', 'a b']);

		parse_str($request->getBody(), $decoded);
		self::assertSame(['10', '20', 'a b'], $decoded['ids']);
	}

	public function testPreEncodedBodyCanBeSetWithoutReencoding(): void
	{
		$request = (new RequestParams())->setBody('value=already%20encoded');

		self::assertSame('value=already%20encoded', $request->getBody());
	}

	public function testAddingBodyParametersDoesNotReencodeExistingValues(): void
	{
		$request = (new RequestParams())
			->setBodyParam('first', 'one two')
			->setBodyParam('second', 'a+b');

		parse_str($request->getBody(), $decoded);
		self::assertSame(['first' => 'one two', 'second' => 'a+b'], $decoded);
	}

	public static function scalarQueryProvider(): array
	{
		return [
			'plain' => ['plain', 'plain'],
			'space' => ['one two', 'one+two'],
			'special characters' => ['a+b & c/%', 'a%2Bb+%26+c%2F%25'],
			'unicode' => ['שלום мир', '%D7%A9%D7%9C%D7%95%D7%9D+%D0%BC%D0%B8%D1%80'],
		];
	}

	public static function bodyProvider(): array
	{
		return [
			'plain' => ['plain', 'value=plain'],
			'space' => ['one two', 'value=one+two'],
			'plus' => ['a+b', 'value=a%2Bb'],
			'percent' => ['50%', 'value=50%25'],
			'ampersand and equals' => ['a&b=c', 'value=a%26b%3Dc'],
			'unicode' => ['שלום', 'value=%D7%A9%D7%9C%D7%95%D7%9D'],
		];
	}
}
