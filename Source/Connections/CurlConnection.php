<?php
namespace Gazelle\Connections;


use Gazelle\Exceptions\Curl\CurlException;
use Gazelle\IConnection;
use Gazelle\ResponseData;
use Gazelle\ErrorHandler;
use Gazelle\IRequestData;
use Gazelle\IResponseData;
use Gazelle\IRequestConfig;
use Gazelle\Utils\HeadersParser;
use Gazelle\Utils\ICurlOptions;


class CurlConnection implements IConnection
{
	private function setOptions($conn, ICurlOptions $from): void
	{
		$options = $from->toCurlOptions();
		
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
	
	private function executeCurl($conn, ResponseData $responseData): ResponseData
	{
		$body = curl_exec($conn);
		
		if ($body === false)
		{
			ErrorHandler::handleCurlException($conn, $responseData->requestData(), $responseData->requestConfig());
		}
		
		$this->parseCurlOutput($conn, $body, $responseData);
		
		return $responseData;
	}
	
	private function parseResponse($conn, ResponseData $responseData): ResponseData
	{
		$responseData->setCode(curl_getinfo($conn, CURLINFO_RESPONSE_CODE));
		return $responseData;
	}
	
	
	private function send($conn, IRequestData $requestData, IRequestConfig $config): IResponseData
	{
		$response = new ResponseData($requestData, $config);
		
		$this->setOptions($conn, $config);
		$this->setOptions($conn, $requestData);
		
		$response = $this->executeCurl($conn, $response);
		$response = $this->parseResponse($conn, $response);
		
		ErrorHandler::handle($response);
		
		return $response;
	}
	
	
	public function request(IRequestData $requestData, IRequestConfig $config): IResponseData
	{
		$conn = curl_init();
		
		try
		{
			return $this->send($conn, $requestData, $config);
		}
		finally
		{
			curl_close($conn);
		}
	}
}