<?php
namespace Gazelle;


use Gazelle\Exceptions\GazelleException;
use Gazelle\Exceptions\ResponseException;
use Gazelle\Exceptions\Response\Unexpected\MissingJSONFieldException;
use Gazelle\Exceptions\Response\Unexpected\InvalidJSONResponseException;


class Request extends RequestParams implements IRequest
{
	/** @var IConnection */
	private $connection = null;
	
	/** @var GazelleException */
	private $lastException = null;
	
	
	private function sendWithMethod(string $method): IResponse
	{
		$this->setMethod($method);
		return $this->send();
	}
	
	private function trySendWithMethod(string $method): ?IResponse
	{
		$this->setMethod($method);
		return $this->trySend();
	}
	
	
	public function __construct(IConnection $connection)
	{
		parent::__construct();
		$this->connection = $connection;
	}
	
	public function __clone()
	{
		parent::__clone();
		
		$this->connection = clone $this->connection;
		$this->lastException = null;
	}
	
	
	public function send(): IResponse
	{
		$this->lastException = null;
		return $this->connection->request($this);
	}
	
	public function trySend(): ?IResponse
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
	
	
	public function get(): IResponse
	{
		return $this->sendWithMethod(HTTPMethod::GET);
	}
	
	public function put(): IResponse
	{
		return $this->sendWithMethod(HTTPMethod::PUT);
	}
	
	public function post(): IResponse
	{
		return $this->sendWithMethod(HTTPMethod::POST);
	}
	
	public function head(): IResponse
	{
		return $this->sendWithMethod(HTTPMethod::HEAD);
	}
	
	public function delete(): IResponse
	{
		return $this->sendWithMethod(HTTPMethod::DELETE);
	}
	
	public function options(): IResponse
	{
		return $this->sendWithMethod(HTTPMethod::OPTIONS);
	}
	
	public function patch(): IResponse
	{
		return $this->sendWithMethod(HTTPMethod::PATCH);
	}
	
	public function trace(): IResponse
	{
		return $this->sendWithMethod(HTTPMethod::TRACE);
	}
	
	
	public function tryGet(): ?IResponse
	{
		return $this->trySendWithMethod(HTTPMethod::GET);
	}
	
	public function tryPut(): ?IResponse
	{
		return $this->trySendWithMethod(HTTPMethod::PUT);
	}
	
	public function tryPost(): ?IResponse
	{
		return $this->trySendWithMethod(HTTPMethod::POST);
	}
	
	public function tryHead(): ?IResponse
	{
		return $this->trySendWithMethod(HTTPMethod::HEAD);
	}
	
	public function tryDelete(): ?IResponse
	{
		return $this->trySendWithMethod(HTTPMethod::DELETE);
	}
	
	public function tryOptions(): ?IResponse
	{
		return $this->trySendWithMethod(HTTPMethod::OPTIONS);
	}
	
	public function tryPatch(): ?IResponse
	{
		return $this->trySendWithMethod(HTTPMethod::PATCH);
	}
	
	public function tryTrace(): ?IResponse
	{
		return $this->trySendWithMethod(HTTPMethod::TRACE);
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
		$result = $this->send();
		$data = $result->getJSON();
		
		if (is_null($data))
		{
			throw new InvalidJSONResponseException($result);
		}
		
		return $data;
	}
	
	public function queryJSONField(string $field)
	{
		$result = $this->send();
		$jason = $result->getJSON();
		
		if (is_array($jason) && isset($jason[$field]))
		{
			return $jason[$field];
		}
		
		throw new MissingJSONFieldException($result, $field);
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
	
	public function tryQueryJSONField(string $field, $default = null)
	{
		try
		{
			return $this->queryJSONField($field);
		}
		catch (MissingJSONFieldException $e)
		{
			return $default;
		}
	}
	
	
	public function getLastException(): ?GazelleException
	{
		return $this->lastException;
	}
	
	public function hasError(): bool
	{
		return !is_null($this->lastException);
	}
	
	public function close(): void
	{
		if ($this->connection)
		{
			$this->connection = null;
		}
	}
	
	
	public function throwLastException(): void
	{
		if ($this->lastException)
		{
			throw $this->lastException;
		}
	}
}