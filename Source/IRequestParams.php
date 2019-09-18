<?php
namespace Gazelle;


use Structura\URL;


interface IRequestParams extends IRequestConfig
{
	public function setConnectionTimeout(float $sec): IRequestParams;
	public function setExecutionTimeout(float $sec, ?float $connectionSec = null): IRequestParams;
	public function setMaxRedirects(int $max): IRequestParams;
	public function setCurlOption(int $option, $value): IRequestParams;
	public function setCurlOptions(array $options): IRequestParams;
	public function setParseResponseForErrors(bool $throw): IRequestParams;
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
	 * @return IRequestParams
	 */
	public function setURL($url): IRequestParams;
	
	public function setPort(int $port): IRequestParams;
	public function setScheme(string $scheme): IRequestParams;
	public function setDomain(string $domain): IRequestParams;
	public function addPath(string $path, bool $clean = true): IRequestParams;
	public function setPath(string $path): IRequestParams;
	
	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return IRequestParams
	 */
	public function setQueryParam(string $name, $value): IRequestParams;
	
	/**
	 * @param string[]|string[][] $params
	 * @return IRequestParams
	 */
	public function setQueryParams(array $params): IRequestParams;
	
	public function setMethod(string $method): IRequestParams;
	public function setHeader(string $header, string $value): IRequestParams;
	public function setHeaders(array $headers, bool $mergeSingleValue = false): IRequestParams;
	public function setBody($body = null): IRequestParams;
	
	/**
	 * @param array|\stdClass $body
	 * @return IRequestParams
	 */
	public function setJsonBody($body): IRequestParams;
}