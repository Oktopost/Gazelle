<?php
namespace Gazelle;


use Structura\URL;

use Gazelle\Utils\IWithCurlOptions;


interface IRequestParams extends IWithCurlOptions
{
	public function getMethod(): string;
	public function getURL(): string;
	public function getURLObject(): URL;
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
	public function setBody(?string $body): IRequestParams;
	
	/**
	 * @param array|\stdClass $body
	 * @return IRequestParams
	 */
	public function setJsonBody($body): IRequestParams;
}