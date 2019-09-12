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
	private $curl = null;
	
	
	private function setOptions(IWithCurlOptions $from): void
	{
		$options = $from->getAllCurlOptions();
		
		if ($options)
		{
			curl_setopt_array($this->curl, $options);
		}
	}
	
	private function parseCurlOutput(string $output, ResponseData $responseData): void
	{
		$headerSize = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
		
		$body = substr($output, $headerSize);
		$headers = substr($output, 0, $headerSize);
		$headers = HeadersParser::parseLastRequestHeaders($headers, true);
		
		$responseData->setBody($body);
		$responseData->setHeaders($headers);
	}
	
	private function parseResponseInfo(IRequestConfig $config, RequestMetaData $data): void
	{
		$data->setRedirects(curl_getinfo($this->curl, CURLINFO_REDIRECT_COUNT) ?? 0);
		
		$flags = array_flip($config->getCurlInfoOptions());
		unset($flags[CURLINFO_REDIRECT_COUNT]);
		
		foreach ($flags as $flag => $val)
		{
			$value = curl_getinfo($this->curl, $flag);
			$data->setInfo($flag, $value);
		}
	}
	
	private function executeCurl(IRequestParams $requestData): ResponseData
	{
		$startTime = microtime(true);
		$body = curl_exec($this->curl);
		$endTime = microtime(true);
		
		$metaData = new RequestMetaData($startTime, $endTime);
		$response = new ResponseData($requestData, $metaData);
		
		if ($body === false)
		{
			ErrorHandler::handleCurlException($this->curl, $response);
		}
		
		$this->parseCurlOutput($body, $response);
		$this->parseResponseInfo($requestData, $metaData);
		
		return $response;
	}
	
	private function parseResponse(ResponseData $responseData): ResponseData
	{
		$responseData->setCode(curl_getinfo($this->curl, CURLINFO_RESPONSE_CODE));
		return $responseData;
	}
	
	
	private function send(IRequestParams $requestData): IResponseData
	{
		$this->setOptions($requestData);
		
		$response = $this->executeCurl($requestData);
		$response = $this->parseResponse($response);
		
		if ($requestData->getParseResponseForErrors())
		{
			ErrorHandler::handle($response);
		}
		
		return $response;
	}
	
	
	public function __destruct()
	{
		if ($this->curl)
		{
			curl_close($this->curl);
			unset($this->curl);
		}
	}
	
	
	public function request(IRequestParams $requestData): IResponseData
	{
		if (!$this->curl)
		{
			$this->curl = curl_init();
		}
		
		return $this->send($requestData);
	}
}