<?php
namespace Gazelle;


use Structura\URL;


interface IRequestParams extends IRequestConfig
{
	public function getTags(): array;
	
	/**
	 * @param array $tags
	 * @return static
	 */
	public function addTags(array $tags): IRequestParams;
	
	/**
	 * @param float $sec
	 * @return static
	 */
	public function setConnectionTimeout(float $sec): IRequestParams;
	
	/**
	 * @param float $sec
	 * @param float|null $connectionSec
	 * @return static
	 */
	public function setExecutionTimeout(float $sec, ?float $connectionSec = null): IRequestParams;
	
	/**
	 * @param int $max
	 * @return static
	 */
	public function setMaxRedirects(int $max): IRequestParams;
	
	/**
	 * @param int $option
	 * @param string|int|array $value
	 * @return static
	 */
	public function setCurlOption(int $option, $value): IRequestParams;
	
	/**
	 * @param array $options
	 * @return static
	 */
	public function setCurlOptions(array $options): IRequestParams;
	
	/**
	 * @param bool $throw
	 * @return static
	 */
	public function setParseResponseForErrors(bool $throw): IRequestParams;
	
	/**
	 * @param bool $reuse
	 * @return static
	 */
	public function setIsConnectionReused(bool $reuse): IRequestParams;
	
	public function clearCurlInfoOptions(): void;
	
	/**
	 * @param int|int[] $flag
	 */
	public function setCurlInfoOptions($flag): void;
	
	
	public function resetParams(): void;
	
	public function getMethod(): string;
	public function getURLString(): string;
	public function getURL(): URL;
	public function getPath(): string;
	public function getScheme(): string;
	public function getDomain(): string;
	
	/**
	 * @param string $name
	 * @return string|string[]|null
	 */
	public function getQueryParam(string $name);
	
	public function getQueryParams(): array;
	public function getQueryString(): string;
	public function getBody(): ?string;
	public function getHeaders(): array;
	
	/**
	 * @param string $header
	 * @return string|string[]|null
	 */
	public function getHeader(string $header);
	
	
	/**
	 * @param string|URL $url
	 * @return static
	 */
	public function setURL($url): IRequestParams;
	
	/**
	 * @param int $port
	 * @return static
	 */
	public function setPort(int $port): IRequestParams;
	
	/**
	 * @param string $scheme
	 * @return static
	 */
	public function setScheme(string $scheme): IRequestParams;
	
	/**
	 * @param string $domain
	 * @return static
	 */
	public function setDomain(string $domain): IRequestParams;
	
	/**
	 * @param string $path
	 * @param bool $clean
	 * @return static
	 */
	public function addPath(string $path, bool $clean = true): IRequestParams;
	
	/**
	 * @param string $path
	 * @return static
	 */
	public function setPath(string $path): IRequestParams;
	
	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return static
	 */
	public function setQueryParam(string $name, $value): IRequestParams;
	
	/**
	 * @param string[]|string[][] $params
	 * @return static
	 */
	public function setQueryParams(array $params): IRequestParams;
	
	/**
	 * @param string $method
	 * @return static
	 */
	public function setMethod(string $method): IRequestParams;
	
	/**
	 * @param string $header
	 * @param string $value
	 * @return static
	 */
	public function setHeader(string $header, string $value): IRequestParams;
	
	/**
	 * @param array $headers
	 * @param bool $mergeSingleValue
	 * @return static
	 */
	public function setHeaders(array $headers, bool $mergeSingleValue = false): IRequestParams;
	
	/**
	 * @param null $body
	 * @return static
	 */
	public function setBody($body = null): IRequestParams;
	
	/**
	 * @param array|\stdClass $body
	 * @return static
	 */
	public function setJsonBody($body): IRequestParams;
}