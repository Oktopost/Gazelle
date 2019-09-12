<?php
namespace Gazelle\Decorators;


use Gazelle\IResponseData;
use Gazelle\IRequestParams;
use Gazelle\AbstractConnectionDecorator;


abstract class AbstractChainDecorator extends AbstractConnectionDecorator
{
	protected function before(IRequestParams $requestParams): void {}
	protected function after(IResponseData $requestParams): void {}
	protected function onError(IRequestParams $requestParams, \Throwable $t): void {}
	protected function finally(IRequestParams $requestParams, ?IResponseData $responseData, ?\Throwable $t): void {}
	
	
	public function request(IRequestParams $requestData): IResponseData
	{
		$responseData = null;
		$error = null;
		
		$this->before($requestData);
		
		try
		{
			$responseData = $this->invokeChild($requestData);
			$this->after($responseData);
		}
		catch (\Throwable $error)
		{
			$this->onError($requestData, $error);
		}
		finally
		{
			$this->finally($requestData, $responseData, $error);
		}
		
		return $responseData;
	}
}