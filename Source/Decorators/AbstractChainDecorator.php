<?php
namespace Gazelle\Decorators;


use Gazelle\IResponse;
use Gazelle\IRequestParams;
use Gazelle\AbstractConnectionDecorator;


abstract class AbstractChainDecorator extends AbstractConnectionDecorator
{
	protected function before(IRequestParams $requestParams): void {}
	protected function after(IResponse $requestParams): void {}
	protected function onError(IRequestParams $requestParams, \Throwable $t): void {}
	protected function finally(IRequestParams $requestParams, ?IResponse $responseData, ?\Throwable $t): void {}
	
	
	public function request(IRequestParams $requestData): IResponse
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