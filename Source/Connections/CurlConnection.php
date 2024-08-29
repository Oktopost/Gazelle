<?php
namespace Gazelle\Connections;


use Gazelle\Response;
use Gazelle\IResponse;
use Gazelle\IConnection;
use Gazelle\IRequestParams;

use Gazelle\Utils\ErrorHandler;


class CurlConnection implements IConnection
{
	private \CurlHandle|null $curl = null;
	
	
	private function executeCurl(IRequestParams $requestData): Response
	{
		$startTime = microtime(true);
		$body = curl_exec($this->curl);
		$endTime = microtime(true);
		
		return CurlParser::response(
			$this->curl, $requestData,
			$startTime, $endTime,
			$body
		);
	}
	
	private function parseResponse(Response $responseData): Response
	{
		$responseData->setCode(curl_getinfo($this->curl, CURLINFO_RESPONSE_CODE));
		return $responseData;
	}
	
	
	private function send(IRequestParams $requestData): IResponse
	{
		CurlParser::request($this->curl, $requestData);
		
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
	
	
	public function request(IRequestParams $requestData): IResponse
	{
		CurlParser::validate($requestData);
		
		if (!$this->curl)
		{
			$this->curl = curl_init();
		}
		else
		{
			curl_reset($this->curl);
		}
		
		return $this->send($requestData);
	}
}