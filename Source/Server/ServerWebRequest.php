<?php
namespace Gazelle\Server;


use Structura\URL;

use WebCore\HTTP\Requests\StandardWebRequest;
use WebCore\IInput;
use WebCore\Method;
use WebCore\IWebRequest;
use WebCore\HTTP\Utilities;
use WebCore\Base\HTTP\IRequestFiles;
use WebCore\Inputs\FromArray;


class ServerWebRequest implements IWebRequest
{
	public ?string	$url			= null;
	public ?string	$uri			= null;
	public ?string	$host			= null;
	public ?bool	$isHttps		= null;
	public ?string	$method			= null;
	public array	$headers		= [];
	public array	$cookies		= [];
	public array	$get			= [];
	public array 	$params			= [];
	public ?string	$body			= null;
	
	
	public function isMethod(string $method): bool 
	{ 
		return $this->getMethod() == $method; 
	}
	
	public function isGet(): bool 
	{ 
		return $this->getMethod() == Method::GET; 
	}
	
	public function isPost(): bool 
	{ 
		return $this->getMethod() == Method::POST; 
	}
	
	public function isPut(): bool 
	{ 
		return $this->getMethod() == Method::PUT; 
	}
	
	public function isDelete(): bool 
	{ 
		return $this->getMethod() == Method::DELETE; 
	}
	
	public function isHttp(): bool 
	{ 
		return !$this->isHttps(); 
	}
	
	
	public function getHeaders(bool $caseSensitive = false): IInput 
	{ 
		return new FromArray($this->headers); 
	}
	
	public function getCookies(): IInput 
	{ 
		return new FromArray($this->cookies); 
	}
	
	public function getCookiesArray(): array 
	{ 
		return $this->cookies;	
	}
	
	public function getCookie(string $cookie, ?string $default = null): ?string 
	{ 
		return $this->getCookies()->string($cookie, $default); 
	}
	
	public function hasCookie(string $cookie): bool 
	{ 
		return $this->getCookies()->has($cookie); 
	}
	
	public function getParams(): IInput 
	{ 
		return new FromArray($this->getParamsArray()); 
	}
	
	public function getParam(string $param, ?string $default = null): ?string 
	{ 
		return $this->getParams()->string($param, $default); 
	}
	
	public function hasParam(string $param): bool 
	{ 
		return $this->getParams()->has($param); 
	}
	
	public function getQuery(): IInput 
	{ 
		return new FromArray($this->getQueryArray()); 
	}
	
	public function getQueryArray(): array 
	{ 
		return $this->get; 
	}
	
	public function getQueryParam(string $param, ?string $default = null): ?string 
	{ 
		return $this->getQuery()->string($param, $default); 
	}
	
	public function hasQueryParam(string $param): bool 
	{ 
		return $this->getQuery()->has($param); 
	} 
	
	public function getPost(): IInput 
	{ 
		return new FromArray($this->getPostArray()); 
	}
	
	public function getPostArray(): array 
	{ 
		return $_POST; 
	}
	
	public function getPostParam(string $param, ?string $default = null): ?string 
	{ 
		return $this->getPost()->string($param, $default); 
	}
	
	public function hasPostParam(string $param): bool 
	{ 
		return $this->getPost()->has($param); 
	}
	
	
	public function getMethod(): string
	{
		if (is_null($this->method))
			$this->method = $_SERVER['REQUEST_METHOD'] ?? Method::UNKNOWN;
		
		return $this->method;
	}
	
	public function isHttps(): bool
	{
		return $this->isHttps;
	}
	
	public function getUserAgent(?string $default = null): ?string
	{
		return Utilities\UserAgentExtractor::get($this, $default);
	}
	
	public function getHeader(string $header, ?string $default = null, bool $caseSensitive = false): ?string
	{
		if (!$caseSensitive)
			$header = strtolower($header);
		
		$headers = Utilities::getAllHeaders($caseSensitive);
		return $headers[$header] ?? $default;
	}
	
	public function hasHeader(string $header, bool $caseSensitive = false): bool
	{
		if (!$caseSensitive)
			$header = strtolower($header);
		
		return key_exists($header, Utilities::getAllHeaders($caseSensitive));
	}
	
	public function getHeadersArray(bool $caseSensitive = false): array
	{
		return Utilities::getAllHeaders($caseSensitive);
	}
	
	public function getParamsArray(): array
	{
		return $this->params;
	}
	
	public function getRequestParams(): IInput 
	{
		return new FromArray($this->getRequestParamsArray());
	}
	
	public function getRequestParamsArray(): array 
	{
		throw new \Exception('unsupported');
	}
	
	public function getRequestParam(string $param, ?string $default = null): ?string 
	{ 
		throw new \Exception('unsupported');
	}
	
	public function hasRequestParam(string $param): bool 
	{ 
		throw new \Exception('unsupported');
	}
	
	
	public function setRouteParams(array $params): void
	{
		throw new \Exception('unsupported');
	}
	
	public function getRouteParams(): IInput
	{
		throw new \Exception('unsupported');
	}
	
	public function getRouteParamsArray(): array
	{
		throw new \Exception('unsupported');
	}
	
	public function getRouteParam(string $param, ?string $default = null): ?string
	{
		throw new \Exception('unsupported');
	}
	
	public function hasRouteParam(string $param): bool
	{
		throw new \Exception('unsupported');
	}
	
	
	public function getPort(): ?int
	{
		throw new \Exception('unsupported');
	}
	
	public function getHost(): string
	{
		return $this->host;
	}
	
	public function getIP(?string $default = null): string
	{
		throw new \Exception('unsupported');
	}
	
	public function getURI(): string
	{
		return $this->uri;
	}
	
	public function getURL(): string
	{
		$protocol = $this->isHttp() ? 'http' : 'https';
		return "{$protocol}://" . $this->getHost() . $this->getURI();
	}
	
	public function getPath(): string
	{
		throw new \Exception('unsupported');
	}
	
	public function getURLObject(): URL
	{
		return new URL($this->url);
	}
	
	
	public function files(): ?IRequestFiles
	{
		throw new \Exception('unsupported');
	}
	
	public function hasFiles(): bool
	{
		throw new \Exception('unsupported');
	}
	
	public function getBody(): string
	{
		return $this->body;
	}
	
	public function getJson(): array
	{
		throw new \Exception('unsupported');
	}
	
	
	public static function fromArray(array $data): ServerWebRequest
	{
		$res = new ServerWebRequest();
		
		$res->url		= $data['url'];
		$res->uri		= $data['uri'];
		$res->host		= $data['host'];
		$res->isHttps	= $data['isHttps'];
		$res->method	= $data['method'];
		$res->headers	= $data['headers'];
		$res->cookies	= $data['cookies'];
		$res->get		= $data['get'];
		$res->params	= $data['params'];
		$res->body		= $data['body'];
		
		return $res;
	}
}