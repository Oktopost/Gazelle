<?php
namespace Gazelle\Utils;


use Traitor\TStaticClass;

use Structura\Arrays;

use Gazelle\HTTPMethod;
use Gazelle\IRequestConfig;
use Gazelle\IRequestSettings;


class OptionsConfig
{
	use TStaticClass;
	
	
	public static function setRedirects(IRequestConfig $config): array
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
	
	public static function setTimeouts(IRequestConfig $config): array 
	{
		$executeTimeout = $config->getExecutionTimeout();
		$connectTimeout = $config->getConnectionTimeout();
		
		return [
			CURLOPT_CONNECTTIMEOUT_MS	=> max(0.0, $connectTimeout * 1000),
			CURLOPT_TIMEOUT_MS			=> max(0.0, $connectTimeout * 1000, $executeTimeout * 1000)
		];
	}
	
	public static function setHeaders(IRequestSettings $data): array 
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
		
		return $result ? [CURLOPT_HTTPHEADER => $headers] : [];
	}
	
	public static function setBody(IRequestSettings $data): array 
	{
		$body = $data->getBody();
		
		return $body ? [CURLOPT_POSTFIELDS => $body] : [];
	}
	
	public static function setMethod(IRequestSettings $data): array 
	{
		$method = $data->getMethod();
		
		if ($method != HTTPMethod::GET)
		{
			return [CURLOPT_CUSTOMREQUEST => $method];
		}
		
		return [];
	}
	
	public static function setURL(IRequestSettings $data): array 
	{
		return [CURLOPT_URL => $data->getURL()];
	}
}