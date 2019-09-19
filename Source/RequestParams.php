<?php
namespace Gazelle;


use Structura\URL;
use Structura\Arrays;
use Structura\Strings;
use Objection\Mapper;
use Objection\LiteObject;

use Gazelle\Utils\OptionsConfig;
use Gazelle\Exceptions\FatalGazelleException;


class RequestParams implements IRequestParams
{
	/** @var URL */
	private $url;
	
	private $body;
	private $method;
	private $headers;
	
	private $throwOnFailedResponse	= true;
	private $connectionTimeout		= 10.0;
	private $executionTimeout		= 10.0;
	private $maxRedirects			= 3;
	
	private $curlOptions = [
		CURLOPT_RETURNTRANSFER	=> 1,
		CURLOPT_HEADER			=> 1
	];
	
	private $curlInfoOptions = [
		CURLINFO_REDIRECT_COUNT,
		CURLINFO_LOCAL_IP,
		CURLINFO_LOCAL_PORT,
		CURLINFO_PRIMARY_IP,
		CURLINFO_PRIMARY_PORT,
		CURLINFO_NAMELOOKUP_TIME,
		CURLINFO_CONNECT_TIME,
		CURLINFO_TOTAL_TIME,
		CURLINFO_REDIRECT_TIME,
		CURLINFO_EFFECTIVE_URL
	];
	
	
	public function __construct()
	{
		$this->resetParams();
	}
	
	public function __clone()
	{
		$this->url = clone $this->url;
	}
	
	
	public function resetParams(): void
	{
		$this->url		= new URL();
		$this->body		= null;
		$this->method 	= HTTPMethod::GET;
		$this->headers	= [];
	}
	
	
	public function getConnectionTimeout(): float
	{
		return $this->connectionTimeout;
	}
	
	public function getExecutionTimeout(): float
	{
		return $this->executionTimeout;
	}
	
	public function getMaxRedirects(): int
	{
		return $this->maxRedirects;
	}
	
	public function getIsConnectionReused(): bool
	{
		return (bool)($this->curlOptions[CURLOPT_FORBID_REUSE] ?? false);
	}
	
	public function getCurlOptions(): array
	{
		return $this->curlOptions;
	}
	
	public function hasCurlOptions(): bool
	{
		return (bool)$this->curlOptions;
	}
	
	public function setIsConnectionReused(bool $reuse): IRequestParams
	{
		if ($reuse)
		{
			unset($this->curlOptions[CURLOPT_FORBID_REUSE]);
		}
		else
		{
			$this->curlOptions[CURLOPT_FORBID_REUSE] = true;
		}
		
		return $this;
	}
	
	
	public function setConnectionTimeout(float $sec): IRequestParams
	{
		$this->connectionTimeout = $sec;
		return $this;
	}
	
	public function setExecutionTimeout(float $sec, ?float $connectionSec = null): IRequestParams
	{
		$this->executionTimeout = $sec;
		
		if ($connectionSec)
		{
			$this->connectionTimeout = $connectionSec;
		}
		
		$this->connectionTimeout = min($this->connectionTimeout, $this->executionTimeout);
		
		return $this;
	}
	
	public function setMaxRedirects(int $max): IRequestParams
	{
		$this->maxRedirects = $max;
		return $this;
	}
	
	public function setCurlOption(int $option, $value): IRequestParams
	{
		$this->curlOptions[$option] = $value;
		return $this;
	}
	
	public function setCurlOptions(array $options): IRequestParams
	{
		$this->curlOptions = array_merge($this->curlOptions, $options);
		return $this;
	}
	
	
	public function setParseResponseForErrors(bool $throw): IRequestParams
	{
		$this->throwOnFailedResponse = $throw;
		return $this;
	}
	
	public function getParseResponseForErrors(): bool
	{
		return $this->throwOnFailedResponse;
	}
	
	
	public function getCurlInfoOptions(): array
	{
		return $this->curlInfoOptions;
	}
	
	public function clearCurlInfoOptions(): void
	{
		$this->curlInfoOptions = [];
	}
	
	/**
	 * @param int|int[] $flag
	 */
	public function setCurlInfoOptions($flag): void
	{
		$this->curlInfoOptions = array_unique(array_merge($this->curlInfoOptions, $flag), SORT_NUMERIC);
	}
	
	
	public function getURLString(): string
	{
		return $this->url->url();
	}
	
	public function getURL(): URL
	{
		return $this->url;
	}
	
	public function getPath(): string
	{
		return $this->url->Path;
	}
	
	public function getScheme(): string
	{
		return $this->url->Scheme;
	}
	
	public function getDomain(): string
	{
		return $this->url->Host;
	}
	
	/**
	 * @param string $name
	 * @return string|string[]|null
	 */
	public function getQueryParam(string $name)
	{
		return $this->url->Query[$name] ?? null;
	}
	
	public function getQueryParams(): array
	{
		return $this->url->Query ?? [];
	}
	
	public function getQueryString(): string
	{
		$url = new URL();
		$url->Query = $this->url->Query;
		
		return $this->url->url();
	}
	
	public function getBody(): ?string
	{
		return $this->body;
	}
	
	public function getHeaders(): array
	{
		return $this->headers;
	}
	
	/**
	 * @param string $header
	 * @return string|string[]|null
	 */
	public function getHeader(string $header)
	{
		return $this->headers[$header] ?? null;
	}
	
	public function getMethod(): string
	{
		return $this->method;
	}
	
	
	/**
	 * @param string|URL $url
	 * @return IRequestParams|static
	 */
	public function setURL($url): IRequestParams
	{
		if (is_string($url))
		{
			$this->url->setUrl($url);
		}
		else
		{
			$this->url = $url;
		}
		
		return $this;
	}
	
	/**
	 * @param int $port
	 * @return IRequestParams
	 */
	public function setPort(int $port): IRequestParams
	{
		$this->url->Port = $port;
		return $this;
	}
	
	/**
	 * @param string $scheme
	 * @return IRequestParams|static
	 */
	public function setScheme(string $scheme): IRequestParams
	{
		$this->url->Scheme = $scheme;
		return $this;
	}
	
	/**
	 * @param string $domain
	 * @return IRequestParams|static
	 */
	public function setDomain(string $domain): IRequestParams
	{
		$this->url->Host = $domain;
		return $this;
	}
	
	/**
	 * @param string $path
	 * @param bool $clean
	 * @return IRequestParams|static
	 */
	public function addPath(string $path, bool $clean = true): IRequestParams
	{
		$current = $this->url->Path;
		
		if ($clean && $current)
		{
			if (Strings::isEndsWith($current, '/'))
			{
				$newPath = $current . Strings::trimStart($path, '/');
			}
			else if (!Strings::isStartsWith($path, '/'))
			{
				$newPath = $current . Strings::trimStart($path, '/');
			}
			else
			{
				$newPath = $current . $path;
			}
		}
		else
		{
			$newPath = Strings::trimStart($path, '/');
		}
		
		$this->url->Path = $newPath;
		
		return $this;
	}
	
	/**
	 * @param string $path
	 * @return IRequestParams|static
	 */
	public function setPath(string $path): IRequestParams
	{
		$this->url->Path = $path;
		return $this;
	}
	
	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return IRequestParams|static
	 */
	public function setQueryParam(string $name, $value): IRequestParams
	{
		$this->url->setQueryParams([$name => $value]);
		return $this;
	}
	
	/**
	 * @param string[]|string[][] $params
	 * @return IRequestParams|static
	 */
	public function setQueryParams(array $params): IRequestParams
	{
		$this->url->setQueryParams($params);
		return $this;
	}
	
	/**
	 * @param string $method
	 * @return IRequestParams|static
	 */
	public function setMethod(string $method): IRequestParams
	{
		$this->method = $method;
		return $this;
	}
	
	/**
	 * @param string $header
	 * @param string $value
	 * @return IRequestParams|static
	 */
	public function setHeader(string $header, string $value): IRequestParams
	{
		$this->headers[$header] = $value;
		return $this;
	}
	
	/**
	 * @param array $headers
	 * @param bool $mergeSingleValue
	 * @return IRequestParams|static
	 */
	public function setHeaders(array $headers, bool $mergeSingleValue = false): IRequestParams
	{
		if (!$mergeSingleValue)
		{
			$this->headers = array_merge($this->headers, $headers);
		}
		else
		{
			foreach ($headers as $key => $value)
			{
				if (isset($this->headers[$key]))
				{
					$value = Arrays::merge($this->headers[$key], $value);
				}
				
				$this->headers[$key] = $value;
			}
		}
		
		return $this;
	}
	
	/**
	 * @param null|mixed $body
	 * @return IRequestParams|static
	 */
	public function setBody($body = null): IRequestParams
	{
		if (is_null($body))
		{
			$this->body = null;
		}
		else if (is_string($body))
		{
			$this->body = $body;
		}
		else if ($body instanceof LiteObject)
		{
			$body = Mapper::getArrayFor($body);
			$this->body = jsonencode($body);
		}
		else if (is_array($body) || ($body instanceof \stdClass))
		{
			$this->body = jsonencode($body);
		}
		else
		{
			throw new FatalGazelleException('Invalid data type passed');
		}
		
		return $this;
	}
	
	/**
	 * @param array|\stdClass $body
	 * @return IRequestParams|static
	 */
	public function setJsonBody($body): IRequestParams
	{
		$this->body = jsonencode($body);
		
		if (is_null($this->body))
		{
			throw new FatalGazelleException(
				'Body must be null, string or an object serializable with json_encode');
		}
		
		return $this;
	}
	
	
	public function getAllCurlOptions(): array
	{
		return OptionsConfig::generate($this);
	}
}