<?php
namespace Gazelle\Utils;


use Structura\Arrays;
use Structura\Strings;

use Traitor\TStaticClass;


class HeadersParser
{
	use TStaticClass;
	
	
	private static function parseSingleHeader(string $header): array
	{
		$result = explode(': ', $header, 2);
		
		if (isset($result[1]))
			return $result;
		
		return [rtrim(trim($header), ':'), ''];
	}
	
	
	public static function getRequestHeaders(string $source): array
	{
		$source = str_replace("\r", '', $source);
		return array_filter(explode("\n\n", $source));
	}
	
	
	public static function parseAllHeaders($source, bool $allowMultipleValues = false): array
	{
		if (is_string($source))
		{
			$source = self::getRequestHeaders($source);
		}
		
		$all = [];
		
		foreach ($source as $headersSet)
		{
			$all[] = self::parseSingleRequestHeaders($headersSet, $allowMultipleValues);
		}
		
		return $all;
	}
	
	public static function parseLastRequestHeaders($source, bool $allowMultipleValues = false): array
	{
		if (is_string($source))
		{
			$source = self::getRequestHeaders($source);
		}
		
		$lastHeaders = Arrays::last($source);
		
		return self::parseSingleRequestHeaders($lastHeaders, $allowMultipleValues);
	}
	
	public static function parseSingleRequestHeaders(string $requestSource, bool $allowMultipleValues = false): array
	{
		$headers = [];
		$requestSource = trim($requestSource);
		$requestSource = str_replace("\r", '', $requestSource);
		
		$requestSource = explode("\n", $requestSource);
		
		
		if ($requestSource && Strings::contains($requestSource[0], ':'))
		{
			[$key, $value] = self::parseSingleHeader($requestSource[0]);
			$headers[$key] = $value;
		}
		
		for ($i = 1; $i < count($requestSource); $i++)
		{
			[$key, $value] = self::parseSingleHeader($requestSource[$i]);
			
			if (isset($headers[$key]) && $allowMultipleValues)
			{
				$headers[$key] = Arrays::merge($headers[$key], $value);
			}
			else
			{
				$headers[$key] = $value;
			}
		}
		
		return $headers;
	}
}