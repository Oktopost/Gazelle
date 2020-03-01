<?php
namespace Gazelle\Decorators;


use Gazelle\IResponse;
use Gazelle\IRequestParams;
use Gazelle\AbstractConnectionDecorator;
use Gazelle\Exceptions\GazelleException;
use Gazelle\Response;


abstract class AbstractMaskedRequestDecorator extends AbstractConnectionDecorator
{
	private const MASK_VALUE = '--masked--';
	
	
	/**
	 * @param IRequestParams|Response $requestData
	 */
	private function maskHeaders($requestData): void
	{
		foreach ($this->getMaskedHeaders() as $header)
		{
			$value = $requestData->getHeader($header);

			if ($value)
			{
				$value = $this->getMaskedValue();
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
				$value = $this->getMaskedValue();
				$requestData->setQueryParam($queryParam, $value);
			}
		}
	}
	
	private function maskRequest(IRequestParams $requestData): void
	{
		$this->maskHeaders($requestData);
		$this->maskQueryParams($requestData);
	}
	
	private function maskResponse(Response $responseData): void
	{
		$this->maskHeaders($responseData);
	}
	
	private function process(bool $success, IRequestParams $request, ?IResponse $response, ?GazelleException $ge): void
	{
		$requestCopy = null;
		$responseCopy = null;
		
		if ($response)
		{
			$responseCopy = Response::copy($response);
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
		
		if ($ge)
		{
			throw $ge;
		}
	}
	
	
	protected abstract function onSuccess(IRequestParams $maskedRequest, IResponse $response): void;
	protected abstract function onError(?IRequestParams $request, ?IResponse $response, \Throwable $t): void;
	
	
	protected function getMaskedHeaders(): array
	{
		return [];
	}
	
	protected function getMaskedQueryParams(): array
	{
		return [];
	}
	
	protected function getMaskedValue(): string
	{
		return self::MASK_VALUE; 
	}
	
	
	public function request(IRequestParams $requestData): IResponse
	{
		$response = null;
		$exception = null;
		
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