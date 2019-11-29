<?php
namespace Gazelle\Utils;


use Traitor\TStaticClass;

use Structura\Arrays;

use Gazelle\HTTPMethod;
use Gazelle\IRequestParams;


class OptionsConfig
{
	use TStaticClass;
	
	
	private static function setRedirects(IRequestParams $config): array
	{
		$maxRedirects = $config->getMaxRedirects();
		
		if ($maxRedirects == 0)
		{
			return [
				CURLOPT_MAXREDIRS		=> 0,
				CURLOPT_FOLLOWLOCATION	=> false
			];
		}
		else if ($maxRedirects < 0)
		{
			return [
				CURLOPT_MAXREDIRS		=> null,
				CURLOPT_FOLLOWLOCATION	=> true
			];
		}
		else
		{
			return [
				CURLOPT_MAXREDIRS		=> $maxRedirects,
				CURLOPT_FOLLOWLOCATION	=> true
			];
		}
	}
	
	private static function setTimeouts(IRequestParams $config): array 
	{
		$executeTimeout = $config->getExecutionTimeout();
		$connectTimeout = $config->getConnectionTimeout();
		
		return [
			CURLOPT_CONNECTTIMEOUT_MS	=> max(0.0, $connectTimeout * 1000),
			CURLOPT_TIMEOUT_MS			=> max(0.0, $connectTimeout * 1000, $executeTimeout * 1000)
		];
	}
	
	private static function setHeaders(IRequestParams $data): array 
	{
		$headers = $data->getHeaders();
		$result = [];
		
		foreach ($headers as $name => $value)
		{
			if (is_null($value))
			{
				$result[] = $name;
				continue;
			}
			
			foreach (Arrays::toArray($value) as $singleValue)
			{
				$result[] = "$name: $singleValue";
			}
		}
		
		return $result ? [CURLOPT_HTTPHEADER => $result] : [];
	}
	
	private static function setBody(IRequestParams $data): array 
	{
		$body = $data->getBody();
		
		return $body ? [CURLOPT_POSTFIELDS => $body] : [];
	}
	
	private static function setMethod(IRequestParams $data): array 
	{
		$options = $data->getCurlOptions();
		$method = $data->getMethod();
		$result = [];
		
		if ($method != HTTPMethod::GET)
		{
			$result[CURLOPT_CUSTOMREQUEST] = $method;
		}
		
		if ($method == HTTPMethod::HEAD && !key_exists(CURLOPT_NOBODY, $options))
		{
			$result[CURLOPT_NOBODY] = true;
		}
		
		return $result;
	}
	
	private static function setURL(IRequestParams $data): array 
	{
		return [CURLOPT_URL => $data->getURLString()];
	}
	
	
	public static function generate(IRequestParams $request): array
	{
		return 
			$request->getCurlOptions() +
			self::setRedirects($request) + 
			self::setTimeouts($request) + 
			self::setURL($request) + 
			self::setBody($request) +  
			self::setMethod($request) +
			self::setHeaders($request);
	}
}