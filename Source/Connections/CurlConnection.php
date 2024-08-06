<?php
namespace Gazelle\Connections;


use Gazelle\IConnection;
use Gazelle\Response;
use Gazelle\IResponse;
use Gazelle\IRequestConfig;
use Gazelle\IRequestParams;
use Gazelle\RequestMetaData;

use Gazelle\Utils\ErrorHandler;
use Gazelle\Utils\HeadersParser;
use Gazelle\Utils\IWithCurlOptions;

use Gazelle\Exceptions\GazelleException;


class CurlConnection implements IConnection
{
	/** @var resource|null */
	private $curl = null;
	
	
	private function validate(IRequestParams $data): void
	{
		$url = $data->getURL();
		
		if (!$url->Path && !$url->Host)
		{
			throw new GazelleException("Malformed URL: {$url->url()}");
		}
	}
	
	private function setOptions(IWithCurlOptions $from): void
	{
		$options = $from->getAllCurlOptions();
		
		if ($options)
		{
			curl_setopt_array($this->curl, $options);
		}
	}
	
	private function parseCurlOutput(string $output, Response $responseData): void
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
	
	private function executeCurl(IRequestParams $requestData): Response
	{
		$startTime = microtime(true);
		$body = curl_exec($this->curl);
		$endTime = microtime(true);
		
		$metaData = new RequestMetaData($startTime, $endTime);
		$response = new Response($requestData, $metaData);
		
		if ($body === false)
		{
			ErrorHandler::handleCurlException($this->curl, $response);
		}
		
		$this->parseCurlOutput($body, $response);
		$this->parseResponseInfo($requestData, $metaData);
		
		return $response;
	}
	
	private function parseResponse(Response $responseData): Response
	{
		$responseData->setCode(curl_getinfo($this->curl, CURLINFO_RESPONSE_CODE));
		return $responseData;
	}
	
	
	private function send(IRequestParams $requestData): IResponse
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
	
	
	public function request(IRequestParams $requestData): IResponse
	{
		$this->validate($requestData);
		
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