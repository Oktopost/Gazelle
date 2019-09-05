<?php
namespace Gazelle;


use Structura\URL;
use Structura\Arrays;
use Structura\Strings;

use Gazelle\Utils\OptionsConfig;
use Gazelle\Exceptions\FatalGazelleException;


class RequestSettings implements IRequestSettings
{
	private $body		= null;
	private $method 	= HTTPMethod::GET;
	private $headers	= [];
	
	/** @var URL */
	private $url;
	
	
	public function __construct()
	{
		$this->url = new URL();
	}
	
	
	public function getURL(): string
	{
		return $this->url->url();
	}
	
	public function getURLObject(): URL
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
	 * @return IRequestSettings|static
	 */
	public function setURL($url): IRequestSettings
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
	 * @return IRequestSettings
	 */
	public function setPort(int $port): IRequestSettings
	{
		$this->url->Port = $port;
		return $this;
	}
	
	/**
	 * @param string $scheme
	 * @return IRequestSettings|static
	 */
	public function setScheme(string $scheme): IRequestSettings
	{
		$this->url->Scheme = $scheme;
		return $this;
	}
	
	/**
	 * @param string $domain
	 * @return IRequestSettings|static
	 */
	public function setDomain(string $domain): IRequestSettings
	{
		$this->url->Host = $domain;
		return $this;
	}
	
	/**
	 * @param string $path
	 * @param bool $clean
	 * @return IRequestSettings|static
	 */
	public function addPath(string $path, bool $clean = true): IRequestSettings
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
	 * @return IRequestSettings|static
	 */
	public function setPath(string $path): IRequestSettings
	{
		$this->url->Path = $path;
		return $this;
	}
	
	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return IRequestSettings|static
	 */
	public function setQueryParam(string $name, $value): IRequestSettings
	{
		$this->url->setQueryParams([$name => $value]);
		return $this;
	}
	
	/**
	 * @param string[]|string[][] $params
	 * @return IRequestSettings|static
	 */
	public function setQueryParams(array $params): IRequestSettings
	{
		$this->url->setQueryParams($params);
		return $this;
	}
	
	/**
	 * @param string $method
	 * @return IRequestSettings|static
	 */
	public function setMethod(string $method): IRequestSettings
	{
		$this->method = $method;
		return $this;
	}
	
	/**
	 * @param string $header
	 * @param string $value
	 * @return IRequestSettings|static
	 */
	public function setHeader(string $header, string $value): IRequestSettings
	{
		$this->headers[$header] = $value;
		return $this;
	}
	
	/**
	 * @param array $headers
	 * @param bool $mergeSingleValue
	 * @return IRequestSettings|static
	 */
	public function setHeaders(array $headers, bool $mergeSingleValue = false): IRequestSettings
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
	 * @param null|string $body
	 * @return IRequestSettings|static
	 */
	public function setBody(?string $body): IRequestSettings
	{
		if (is_null($body))
		{
			$this->body = null;
		}
		else if (is_string($body))
		{
			$this->body = $body;
		}
		else
		{
			throw new FatalGazelleException('Invalid data type passed');
		}
		
		return $this;
	}
	
	/**
	 * @param array|\stdClass $body
	 * @return IRequestSettings|static
	 */
	public function setJsonBody($body): IRequestSettings
	{
		$this->body = jsonencode($body);
		
		if (is_null($this->body))
		{
			throw new FatalGazelleException(
				'Body must be null, string or an object serializable with json_encode');
		}
		
		return $this;
	}
	
	
	public function toCurlOptions(): array
	{
		return 
			OptionsConfig::setURL($this) + 
			OptionsConfig::setBody($this) +  
			OptionsConfig::setMethod($this) +
			OptionsConfig::setHeaders($this);
	}
}