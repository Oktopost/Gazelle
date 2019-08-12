<?php
namespace Gazelle;


use Structura\Arrays;
use Structura\Strings;
use Structura\URL;


class RequestData implements IRequestData
{
	private $body		= null;
	private $method 	= HTTPMethod::GET;
	private $headers	= [];
	
	/** @var URL */
	private $url;
	
	
	
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
	 * @return IRequestData
	 */
	public function setURL($url): IRequestData
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
	
	public function setScheme(string $scheme): IRequestData
	{
		$this->url->Scheme = $scheme;
		return $this;
	}
	
	public function setDomain(string $domain): IRequestData
	{
		$this->url->Host = $domain;
		return $this;
	}
	
	public function addPath(string $path, bool $clean = true): IRequestData
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
	
	public function setPath(string $path): IRequestData
	{
		$this->url->Path = $path;
		return $this;
	}
	
	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return IRequestData
	 */
	public function setQueryParam(string $name, $value): IRequestData
	{
		$this->url->setQueryParams([$name => $value]);
		return $this;
	}
	
	/**
	 * @param string[]|string[][] $params
	 * @return IRequestData
	 */
	public function setQueryParams(array $params): IRequestData
	{
		$this->url->setQueryParams($params);
		return $this;
	}
	
	public function setMethod(string $method): IRequestData
	{
		$this->method = $method;
		return $this;
	}
	
	public function setHeader(string $header, string $value): IRequestData
	{
		$this->headers[$header] = $value;
		return $this;
	}
	
	public function setHeaders(array $headers, bool $mergeSingleValue = false): IRequestData
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
	 * Non string values will be treated as json.
	 * @param string|array|\stdClass $body
	 * @return IRequestData
	 */
	public function setBody($body): IRequestData
	{
		if (is_string($body))
		{
			$this->body = $body;
		}
		else
		{
			$this->body = jsonencode($body);
		}
		
		return $this;
	}
}