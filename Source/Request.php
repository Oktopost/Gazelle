<?php
namespace Gazelle;


use Gazelle\Exceptions\GazelleException;
use Gazelle\Exceptions\ResponseException;
use Gazelle\Exceptions\Response\Unexpected\InvalidJSONResponseException;


class Request extends RequestParams implements IRequest
{
	/** @var IConnectionBuilder */
	private $builder;
	
	/** @var GazelleException */
	private $lastException = null;
	
	
	private function sendWithMethod(string $method): IResponseData
	{
		$this->setMethod($method);
		return $this->send();
	}
	
	private function trySendWithMethod(string $method): ?IResponseData
	{
		$this->setMethod($method);
		return $this->trySend();
	}
	
	
	public function __construct(IConnectionBuilder $builder)
	{
		parent::__construct();
		$this->builder = $builder;
	}
	
	public function __clone()
	{
		$this->builder = clone $this->builder;
	}
	
	
	public function send(): IResponseData
	{
		$this->lastException = null;
		$connection = $this->builder->get();
		return $connection->request($this);
	}
	
	public function trySend(): ?IResponseData
	{
		try
		{
			return $this->send();
		}
		catch (GazelleException $e)
		{
			$this->lastException = $e;
			
			if ($e instanceof ResponseException)
			{
				return $e->response();
			}
			
			return null;
		}
	}
	
	
	public function get(): IResponseData
	{
		return $this->sendWithMethod(HTTPMethod::GET);
	}
	
	public function put(): IResponseData
	{
		return $this->sendWithMethod(HTTPMethod::PUT);
	}
	
	public function post(): IResponseData
	{
		return $this->sendWithMethod(HTTPMethod::POST);
	}
	
	public function head(): IResponseData
	{
		return $this->sendWithMethod(HTTPMethod::HEAD);
	}
	
	public function delete(): IResponseData
	{
		return $this->sendWithMethod(HTTPMethod::DELETE);
	}
	
	public function options(): IResponseData
	{
		return $this->sendWithMethod(HTTPMethod::OPTIONS);
	}
	
	public function patch(): IResponseData
	{
		return $this->sendWithMethod(HTTPMethod::PATCH);
	}
	
	
	public function tryGet(): ?IResponseData
	{
		return $this->trySendWithMethod(HTTPMethod::GET);
	}
	
	public function tryPut(): ?IResponseData
	{
		return $this->trySendWithMethod(HTTPMethod::PUT);
	}
	
	public function tryPost(): ?IResponseData
	{
		return $this->trySendWithMethod(HTTPMethod::POST);
	}
	
	public function tryHead(): ?IResponseData
	{
		return $this->trySendWithMethod(HTTPMethod::HEAD);
	}
	
	public function tryDelete(): ?IResponseData
	{
		return $this->trySendWithMethod(HTTPMethod::DELETE);
	}
	
	public function tryOptions(): ?IResponseData
	{
		return $this->trySendWithMethod(HTTPMethod::OPTIONS);
	}
	
	public function tryPatch(): ?IResponseData
	{
		return $this->trySendWithMethod(HTTPMethod::PATCH);
	}
	
	
	public function queryCode(): int
	{
		return $this->send()->getCode();
	}
	
	public function queryOK(): bool
	{
		return $this->send()->isSuccessful();
	}
	
	public function queryHeaders(): array
	{
		return $this->send()->getHeaders();
	}
	
	public function queryBody(): string
	{
		return $this->send()->getBody();
	}
	
	public function queryJSON(): array
	{
		$this->setMethod(HTTPMethod::GET);
		$result = $this->send()->getJSON();
		
		if (!is_null($result))
		{
			throw new InvalidJSONResponseException($result);
		}
		
		return $result;
	}
	
	
	public function tryQueryCode(): ?int
	{
		$result = $this->tryGet();
		return $result ? $result->getCode() : null;
	}
	
	public function tryQueryOK(): bool
	{
		$result = $this->trySend();
		return $result ? $result->isSuccessful() : false;
	}
	
	public function tryQueryHeaders(bool $defaultAsEmptyArray = false): ?array
	{
		$default = $defaultAsEmptyArray ? [] : null;
		$result = $this->trySend();
		
		return $result ? $result->getHeaders() : $default;
	}
	
	public function tryQueryBody(bool $defaultAsEmptyString = false): ?string
	{
		$default = $defaultAsEmptyString ? '' : null;
		$result = $this->trySend();
		
		return $result ? $result->getBody() : $default;
	}
	
	public function tryQueryJSON(): ?array
	{
		$result = $this->trySend();
		
		if (!$result)
			return null;
		
		$json = $result->getJSON();
		
		if (is_null($json))
		{
			$this->lastException = new InvalidJSONResponseException($result);
			return null;
		}
		
		return $json;
	}
	
	
	public function getLastException(): ?GazelleException
	{
		return $this->lastException;
	}
	
	public function hasError(): bool
	{
		return !is_null($this->lastException);
	}
	
	public function throwLastException(): void
	{
		if ($this->lastException)
		{
			throw $this->lastException;
		}
	}
}