<?php
namespace Gazelle\Decorators;


use Gazelle\IResponse;
use Gazelle\IRequestParams;
use Gazelle\AbstractConnectionDecorator;
use Gazelle\Utils\IP\AbstractIPProviderCache;
use Gazelle\Utils\IP\DNSIPProvider;
use Gazelle\Utils\IP\IIPProvider;
use Gazelle\Utils\IP\FileCacheIPProvider;
use Gazelle\Utils\IP\MemoryCacheIPProvider;
use Structura\URL;


class IPCacheDecorator extends AbstractConnectionDecorator
{
	private IIPProvider $cache;
	private string $host;
	
	
	private function getIPsCSV(): string
	{
		$array = $this->cache->getAllIPs();
		shuffle($array);
		return implode(',', $array);
	}
	
	
	public function __construct(string $host, IIPProvider $provider)
	{
		$this->host = $host;
		$this->cache = $provider;
	}


	public function request(IRequestParams $requestData): IResponse
	{
		$url = $requestData->getURL();
		$port = $url->Port ?: (URL::DEFAULT_PORTS[$url->Scheme] ?? '80');
		$host = $url->Host;
		
		if ($host == $this->host)
		{
			$ips = $this->cache->getAllIPs();
			
			if ($ips)
			{
				$ipsResolve = "$host:$port:{$this->getIPsCSV()}";
				
				$requestData = clone $requestData;
				$requestData->setCurlOption(CURLOPT_RESOLVE, [$ipsResolve]);
			}
		}
		
		return $this->invokeChild($requestData);
	}
	
	
	public static function createFromDNSResolve(?IIPProvider $base,
		string $host, string $cacheKey, int $ttl = 60, ?string $tmpDir = '/tmp'): IPCacheDecorator
	{
		if (!$base)
			$base = new DNSIPProvider($host);
		
		return new IPCacheDecorator($host, 
			AbstractIPProviderCache::createChain(
				$base,
				new FileCacheIPProvider($host, $ttl, $tmpDir),
				new MemoryCacheIPProvider($ttl, $cacheKey, $host)
			)
		);
	}
}