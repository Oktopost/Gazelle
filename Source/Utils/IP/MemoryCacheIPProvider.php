<?php
namespace Gazelle\Utils\IP;


class MemoryCacheIPProvider extends AbstractIPProviderCache
{
	private int $ttl;
	private int $timeout;
	private array $ips = [];
	private ?string $staticKey = null;
	
	
	private static array $staticCache = [];
	
	
	private function getTimeout(): int
	{
		if ($this->ttl <= 0)
		{
			return PHP_INT_MAX;
		}
		else
		{
			return time() + $this->ttl;
		}
	}
	
	private function getAllIPsFromThis(): array
	{
		if ($this->timeout < time())
		{
			$this->ips = $this->getParent()->getAllIPs();
			$this->timeout = $this->getTimeout();
		}
		
		return $this->ips;
	}
	
	private function getAllIPsFromStatic(): array
	{
		$timeout = self::$staticCache[$this->staticKey]['timeout'] ?? 0;
		
		if ($timeout < time())
		{
			self::$staticCache[$this->staticKey]['ips'] = $this->getParent()->getAllIPs();
			self::$staticCache[$this->staticKey]['timeout'] = $this->getTimeout();
		}
		
		return self::$staticCache[$this->staticKey]['ips'] ?? [];
	}
	
	
	public function __construct(int $ttl = 60, ?string $staticGroup = null, ?string $host = null)
	{
		parent::__construct();
		
		$this->ttl = $ttl;
		$this->timeout = 0;
		
		if ($staticGroup)
		{
			if (!$host)
				throw new \Exception('When using static group, host must be set');
			
			$this->staticKey = "$staticGroup:$host";
		}	
	}


	protected function doGetAllIPs(): array
	{
		if ($this->staticKey)
		{
			return $this->getAllIPsFromStatic();
		}
		else
		{
			return $this->getAllIPsFromThis();
		}
	}
}