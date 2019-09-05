<?php
namespace Gazelle;


use Gazelle\Utils\ICurlOptions;
use Structura\URL;


interface IRequestData extends ICurlOptions
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
	 * @return IRequestData
	 */
	public function setURL($url): IRequestData;
	
	public function setPort(int $port): IRequestData;
	public function setScheme(string $scheme): IRequestData;
	public function setDomain(string $domain): IRequestData;
	public function addPath(string $path, bool $clean = true): IRequestData;
	public function setPath(string $path): IRequestData;
	
	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return IRequestData
	 */
	public function setQueryParam(string $name, $value): IRequestData;
	
	/**
	 * @param string[]|string[][] $params
	 * @return IRequestData
	 */
	public function setQueryParams(array $params): IRequestData;
	
	public function setMethod(string $method): IRequestData;
	public function setHeader(string $header, string $value): IRequestData;
	public function setHeaders(array $headers, bool $mergeSingleValue = false): IRequestData;
	public function setBody(?string $body): IRequestData;
	
	/**
	 * @param array|\stdClass $body
	 * @return IRequestData
	 */
	public function setJsonBody($body): IRequestData;
}