<?php
namespace Gazelle;


use Gazelle\Exceptions\Response\Unexpected\InvalidJSONResponseException;
use Structura\Arrays;


class Response implements IResponse
{
	private $code;
	private $headers;
	
	private $body			= null;
	private $bodyCallback	= null;
	
	/** @var IRequestParams */
	private $originalRequest;
	
	/** @var IRequestMetaData */
	private $metaData; 
	
	
	public function __construct(IRequestParams $requestData, IRequestMetaData $metaData)
	{
		$this->originalRequest = clone $requestData;
		$this->metaData = $metaData;
	}
	
	
	public function setCode(int $code): Response
	{
		$this->code = $code;
		return $this;
	}
	
	public function setBody(string $body): Response
	{
		$this->body = $body;
		return $this;
	}
	
	public function setBodyCallback(callable $callback): Response
	{
		$this->bodyCallback = $callback;
		return $this;
	}
	
	public function setHeaders(array $headers): Response
	{
		$this->headers = $headers;
		return $this;
	}
	
	public function setHeader(string $header, string $value): Response
	{
		$this->headers[$header] = $value;
		return $this;
	}
	
	public function getRequestParams(): IRequestParams
	{
		return $this->originalRequest;
	}
	
	public function requestMetaData(): IRequestMetaData
	{
		return $this->metaData;
	}
	
	
	public function getCode(): int
	{
		return $this->code;
	}
	
	public function getHeaders(): array
	{
		return $this->headers;
	}
	
	public function getHeader(string $key, bool $caseSensitive = false): ?string
	{
		$value = $this->headers[$key] ?? null;
		
		if (!$value && $caseSensitive)
		{
			$key = strtolower($key);
			
			foreach ($this->headers as $index => $item)
			{
				if (strtolower($index) == $key)
				{
					$value = $item;
					break;
				}
			}
		}
		
		return is_array($value) ? Arrays::first($value) : $value;
	}
	
	public function hasHeader(string $key): bool
	{
		return isset($this->headers[$key]);
	}
	
	public function hasBody(): bool
	{
		if ($this->body)
			return true;
		
		return (bool)($this->getBody());
	}
	
	public function bodyLength(): int
	{
		return strlen($this->getBody());
	}
	
	public function getBody(): string
	{
		if (is_callable($this->bodyCallback))
		{
			$callback = $this->bodyCallback;
			$this->bodyCallback = null;
			$this->body = $callback();
		}
		
		return $this->body;
	}
	
	public function getJSON(): array
	{
		$body = $this->getBody();
		$result = jsondecode_a($body);
		
		if (!is_array($result))
		{
			throw new InvalidJSONResponseException($this);
		}
		
		return $result;
	}
	
	public function tryGetJSON(): ?array
	{
		$body = $this->getBody();
		$result = jsondecode_a($body);
		
		return (is_array($result) ? $result : null);
	}
	
	public function isSuccessful(): bool
	{
		return $this->code < 400;
	}
	
	public function isComplete(): bool
	{
		return $this->code < 300;
	}
	
	public function isRedirect(): bool
	{
		return 300 <= $this->code && $this->code < 400;
	}
	
	public function isFailed(): bool
	{
		return  400 <= $this->code && $this->code < 600;
	}
	
	public function isServerError(): bool
	{
		return  500 <= $this->code && $this->code < 600;
	}
	
	public function isClientError(): bool
	{
		return  400 <= $this->code && $this->code < 500;
	}
	
	
	public static function copy(IResponse $data): Response
	{
		$result = new Response($data->getRequestParams(), $data->requestMetaData());
		$result->setHeaders($data->getHeaders());
		$result->setBody($data->getBody());
		$result->setCode($data->getCode());
		
		return $result;
	}
}