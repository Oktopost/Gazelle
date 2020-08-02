<?php
namespace Gazelle\Utils;


use Gazelle\Exceptions\CertificateInfoException;
use Gazelle\Exceptions\GazelleException;
use Structura\URL;
use Traitor\TStaticClass;
use Gazelle\CertificateInfo;


class CertificateInfoQuery
{
	use TStaticClass;
	
	
	private const STREAM_PARAMS = ["ssl" => ["capture_peer_cert" => true]];
	
	
	private static function getCertificateURL(string $host): string
	{
		return "ssl://$host:443";
	}
	
	private static function getHost($from): string 
	{
		$host = null;
		
		if ($from instanceof URL)
		{
			$host = $from->Host;
		}
		else if (is_string($from))
		{
			$from = new URL($from);
			$host = $from->Host; 
		}
		else
		{
			throw new GazelleException("Unexpected type for 'from'. Must be string or URL param.");
		}
		
		if (!$host)
		{
			throw new GazelleException("Could not extract domain from {$from->url()}");
		}
		
		return $host;
	}
	
	private static function extractParams(array $response): array
	{
		$data = $response['options']['ssl']['peer_certificate'] ?? [];
		
		if (!$data)
		{
			throw new CertificateInfoException("Got unexpected response", $data);
		}
		
		$info = openssl_x509_parse($data);
		
		if (!$info)
		{
			throw new CertificateInfoException("Got unexpected response", $data);
		}
		
		return $info;
	}	
	
	private static function checkForErrors($data, $errno, $err): void
	{
		if ($data === false)
		{
			throw new CertificateInfoException("Got error from stream_socket_client", null, $errno ?: 0, $err ?: '');
		}
		else if ($errno)
		{
			throw new CertificateInfoException("Got error from stream_socket_client", $data, $errno ?: 0, $err ?: '');
		}
	}
	
	
	public static function getCertificateInfo($from, int $timeout = 10): ?CertificateInfo
	{
		$from = self::getHost($from);
		$certificateURL = self::getCertificateURL($from);
		
		$context = stream_context_create(self::STREAM_PARAMS);
		$data = @stream_socket_client($certificateURL, $errno, $err, $timeout, STREAM_CLIENT_CONNECT, $context);
		
		self::checkForErrors($data, $errno, $err);
		
		$result = stream_context_get_params($data);
		$certificateData = self::extractParams($result);
		
		return CertificateInfo::parse($certificateData);
	}
	
	public static function tryGetCertificateInfo($from, \Throwable &$t = null, int $timeout = 10): ?CertificateInfo
	{
		try
		{
			return self::getCertificateInfo($from, $timeout);
		}
		catch (CertificateInfoException $e)
		{
			$t = $e;
			return null;
		}
	}
}