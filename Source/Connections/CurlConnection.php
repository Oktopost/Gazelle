<?php
namespace Gazelle\Connections;


use Gazelle\IConnection;
use Gazelle\ResponseData;
use Gazelle\IResponseData;
use Gazelle\IRequestConfig;
use Gazelle\IRequestParams;
use Gazelle\RequestMetaData;

use Gazelle\Utils\ErrorHandler;
use Gazelle\Utils\HeadersParser;
use Gazelle\Utils\IWithCurlOptions;


class CurlConnection implements IConnection
{
	private function setOptions($conn, IWithCurlOptions $from): void
	{
		$options = $from->getCurlOptions();
		
		if ($options)
		{
			curl_setopt_array($conn, $options);
		}
	}
	
	private function parseCurlOutput($conn, string $output, ResponseData $responseData): void
	{
		$headerSize = curl_getinfo($conn, CURLINFO_HEADER_SIZE);
		
		$body = substr($output, $headerSize);
		$headers = substr($output, 0, $headerSize);
		$headers = HeadersParser::parseLastRequestHeaders($headers, true);
		
		$responseData->setBody($body);
		$responseData->setHeaders($headers);
	}
	
	private function parseResponseInfo($conn, IRequestConfig $config, RequestMetaData $data): void
	{
		
	}
	
	private function executeCurl($conn, IRequestParams $requestData): ResponseData
	{
		$startTime = microtime(true);
		$body = curl_exec($conn);
		$endTime = microtime(true);
		
		$metaData = new RequestMetaData($startTime, $endTime);
		$response = new ResponseData($requestData, $metaData);
		
		if ($body === false)
		{
			ErrorHandler::handleCurlException($conn, $response);
		}
		
		$this->parseCurlOutput($conn, $body, $response);
		$this->parseResponseInfo($conn, $requestData->getConfig(), $metaData);
		
		
		return $response;
	}
	
	private function parseResponse($conn, ResponseData $responseData): ResponseData
	{
		$responseData->setCode(curl_getinfo($conn, CURLINFO_RESPONSE_CODE));
		return $responseData;
	}
	
	
	private function send($conn, IRequestParams $requestData): IResponseData
	{
		$this->setOptions($conn, $requestData);
		
		$response = $this->executeCurl($conn, $requestData);
		$response = $this->parseResponse($conn, $response);
		
		if ($requestData->getConfig()->getParseResponseForErrors())
		{
			ErrorHandler::handle($response);
		}
		
		return $response;
	}
	
	
	public function request(IRequestParams $requestData): IResponseData
	{
		$conn = curl_init();
		
		try
		{
			return $this->send($conn, $requestData);
		}
		finally
		{
			curl_close($conn);
		}
	}
}