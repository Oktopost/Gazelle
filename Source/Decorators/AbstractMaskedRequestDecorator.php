<?php
namespace Gazelle\Decorators;


use Gazelle\IResponseData;
use Gazelle\IRequestParams;
use Gazelle\AbstractConnectionDecorator;
use Gazelle\Exceptions\GazelleException;
use Gazelle\ResponseData;


abstract class AbstractMaskedRequestDecorator extends AbstractConnectionDecorator
{
	private const MASK_VALUE = '--masked--';
	
	
	/**
	 * @param IRequestParams|ResponseData $requestData
	 */
	private function maskHeaders($requestData): void
	{
		foreach ($this->getMaskedHeaders() as $header)
		{
			$value = $requestData->getHeader($header);

			if ($value)
			{
				$value = static::getMaskedValue();
				$requestData->setHeader($header, $value);
			}
		}
	}
	
	private function maskQueryParams(IRequestParams $requestData): void
	{
		foreach ($this->getMaskedQueryParams() as $queryParam)
		{
			$value = $requestData->getQueryParam($queryParam);

			if ($value)
			{
				$value = static::getMaskedValue();
				$requestData->setQueryParam($queryParam, $value);
			}
		}
	}
	
	private function maskRequest(IRequestParams $requestData): void
	{
		$this->maskHeaders($requestData);
		$this->maskQueryParams($requestData);
	}
	
	private function maskResponse(ResponseData $responseData): void
	{
		$this->maskHeaders($responseData);
	}
	
	private function process(bool $success, IRequestParams $request, ?IResponseData $response, ?GazelleException $ge): void
	{
		$requestCopy = null;
		$responseCopy = null;
		
		if ($response)
		{
			$responseCopy = ResponseData::copy($response);
			$requestCopy = $responseCopy->getRequestParams();
		}
		
		if (!$requestCopy && $ge->request())
		{
			$requestCopy = clone $ge->request();
		}
		
		if (!$requestCopy)
		{
			$requestCopy = clone $request;
		}
		
		$this->maskRequest($requestCopy);
		
		if ($responseCopy)
			$this->maskResponse($responseCopy);
		
		if ($success)
		{
			$this->onSuccess($requestCopy, $responseCopy);
		}
		else
		{
			$this->onError($requestCopy, $responseCopy, $ge);
		}
	}
	
	
	protected abstract function onSuccess(IRequestParams $maskedRequest, IResponseData $response): void;
	protected abstract function onError(?IRequestParams $request, ?IResponseData $response, \Throwable $t): void;
	
	
	protected function getMaskedHeaders(): array
	{
		return [];
	}
	
	protected function getMaskedQueryParams(): array
	{
		return [];
	}
	
	protected static function getMaskedValue(): string { return self::MASK_VALUE; }
	
	
	public function request(IRequestParams $requestData): IResponseData
	{
		$response = null;
		
		$exception = null;
		$isSuccess = false;
		
		try
		{
			$response = $this->invokeChild($requestData);
			$isSuccess = true;
		}
		catch (GazelleException $ge)
		{
			$response = $ge->response() ?? null;
			$exception = $ge;
			$isSuccess = false;
		}
		
		$this->process($isSuccess, $requestData, $response, $exception);
		
		return $response;
	}
}