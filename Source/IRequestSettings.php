<?php
namespace Gazelle;


use Structura\URL;

use Gazelle\Utils\IWithCurlOptions;


interface IRequestSettings extends IWithCurlOptions
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
	 * @return IRequestSettings
	 */
	public function setURL($url): IRequestSettings;
	
	public function setPort(int $port): IRequestSettings;
	public function setScheme(string $scheme): IRequestSettings;
	public function setDomain(string $domain): IRequestSettings;
	public function addPath(string $path, bool $clean = true): IRequestSettings;
	public function setPath(string $path): IRequestSettings;
	
	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return IRequestSettings
	 */
	public function setQueryParam(string $name, $value): IRequestSettings;
	
	/**
	 * @param string[]|string[][] $params
	 * @return IRequestSettings
	 */
	public function setQueryParams(array $params): IRequestSettings;
	
	public function setMethod(string $method): IRequestSettings;
	public function setHeader(string $header, string $value): IRequestSettings;
	public function setHeaders(array $headers, bool $mergeSingleValue = false): IRequestSettings;
	public function setBody(?string $body): IRequestSettings;
	
	/**
	 * @param array|\stdClass $body
	 * @return IRequestSettings
	 */
	public function setJsonBody($body): IRequestSettings;
}