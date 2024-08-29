<?php
namespace Gazelle\Connections;


use Gazelle\Response;
use Gazelle\IRequestConfig;
use Gazelle\IRequestParams;
use Gazelle\RequestMetaData;
use Gazelle\Exceptions\GazelleException;

use Gazelle\Utils\ErrorHandler;
use Gazelle\Utils\HeadersParser;

use Traitor\TStaticClass;


class CurlParser
{
	use TStaticClass;
	
	
	private static function parseCurlOutput(\CurlHandle $curl, string $output, Response $responseData): void
	{
		$headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		
		$body = substr($output, $headerSize);
		$headers = substr($output, 0, $headerSize);
		$headers = HeadersParser::parseLastRequestHeaders($headers, true);
		
		$responseData->setBody($body);
		$responseData->setHeaders($headers);
	}
	
	private static function parseResponseInfo(\CurlHandle $curl, IRequestConfig $config, RequestMetaData $data): void
	{
		$data->setRedirects(curl_getinfo($curl, CURLINFO_REDIRECT_COUNT) ?? 0);
		
		$flags = array_flip($config->getCurlInfoOptions());
		unset($flags[CURLINFO_REDIRECT_COUNT]);
		
		foreach ($flags as $flag => $val)
		{
			$value = curl_getinfo($curl, $flag);
			$data->setInfo($flag, $value);
		}
	}
	
	
	public static function validate(IRequestParams $data): void
	{
		$url = $data->getURL();
		
		if (!$url->Path && !$url->Host)
		{
			throw new GazelleException("Malformed URL: {$url->url()}");
		}
	}
	
	public static function request(?\CurlHandle $curl, IRequestParams $params): \CurlHandle 
	{
		self::validate($params);
		
		$options = $params->getAllCurlOptions();
		
		if (!$curl)
		{
			$curl = curl_init();
		}
		
		if ($options)
		{
			curl_setopt_array($curl, $options);
		}
		
		return $curl;
	}
	
	public static function response(
		?\CurlHandle $curl, IRequestParams $request, 
		float $startTime, float $endTime, 
		string|bool $body): Response
	{
		$metaData = new RequestMetaData($startTime, $endTime);
		$response = new Response($request, $metaData);
		
		if ($body === false)
		{
			ErrorHandler::handleCurlException($curl, $response);
		}
		
		self::parseCurlOutput($curl, $body, $response);
		self::parseResponseInfo($curl, $request, $metaData);
		
		return $response;
	}
}