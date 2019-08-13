<?php
namespace Gazelle;


use Structura\Arrays;

class ResponseData implements IResponseData
{
	private $code;
	private $headers;
	
	private $body			= null;
	private $bodyCallback	= null;
	
	/** @var IRequestData */
	private $originalRequest;
	
	/** @var IRequestConfig */
	private $requestConfig;
	
	
	public function __construct(IRequestData $requestData, IRequestConfig $config)
	{
		$this->originalRequest = $requestData;
		$this->requestConfig = $config;
	}
	
	
	public function setCode(int $code): ResponseData
	{
		$this->code = $code;
		return $this;
	}
	
	public function setBody(string $body): ResponseData
	{
		$this->body = $body;
		return $this;
	}
	
	public function setBodyCallback(callable $callback): ResponseData
	{
		$this->bodyCallback = $callback;
		return $this;
	}
	
	public function setHeaders(array $headers): ResponseData
	{
		$this->headers = $headers;
		return $this;
	}
	
	
	public function requestData(): IRequestData
	{
		return $this->originalRequest;
	}
	
	public function requestConfig(): IRequestConfig
	{
		return $this->requestConfig;
	}
	
	
	public function getCode(): int
	{
		return $this->code;
	}
	
	public function getHeaders(): array
	{
		return $this->headers;
	}
	
	public function getHeader(string $key, bool $firstValue = true): ?string
	{
		$value = $this->headers[$key] ?? null;
		
		if (!$value || is_string($value) || !$firstValue)
		{
			return $value;
		}
		
		return Arrays::first($value);
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
	
	public function getJSON(): ?array
	{
		$body = $this->getBody();
		$result = jsondecode($body);
		
		return (is_array($result) ? $result : null);
	}
	
	public function isSuccessful(): bool
	{
		return $this->code < 400;
	}
	
	public function isRedirect(): bool
	{
		return 300 <= $this->code && $this->code < 400;
	}
	
	public function isFailed(): bool
	{
		return  400 <= $this->code && $this->code < 600;
	}
}