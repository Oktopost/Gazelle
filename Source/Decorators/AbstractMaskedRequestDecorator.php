<?php
namespace Gazelle\Decorators;


use Gazelle\IResponseData;
use Gazelle\IRequestParams;
use Gazelle\Exceptions\GazelleException;
use Gazelle\AbstractConnectionDecorator;


abstract class AbstractMaskedRequestDecorator extends AbstractConnectionDecorator
{
	private const MASK_VALUE = '--masked--';
	
	
	/**
	 * @param IRequestParams|IResponseData $requestData
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
	
	private function maskResponse(IResponseData $responseData): void
	{
		$this->maskHeaders($responseData);
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
		
		try
		{
			$response = $this->invokeChild($requestData);
			
			$maskedRequest = clone $response->getRequestParams();
			$this->maskRequest($maskedRequest);
			$this->maskResponse($response);
			
			$this->onSuccess($maskedRequest, $response);
		}
		catch (GazelleException $t)
		{
			if ($t->request())
			{
				$this->maskRequest($t->request());
			}
			
			if ($t->response())
			{
				$this->maskResponse($t->response());
			}
			
			$this->onError($t->request(), $t->response(), $t);
		}
		
		return $response;
	}
}