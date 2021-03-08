<?php
namespace Gazelle\Utils;


use Gazelle\DNS\DNSResolver;
use Structura\Strings;


class ManagedIPCache
{
	private string $host;
	private ?string $cacheFile;
	private int $ttl;
	
	private ?int $timeout = null;
	private array $ips = [];
	
	
	private function resolve(): void
	{
		$this->ips = DNSResolver::resolveToIPs($this->host);
		$this->timeout = time() + $this->ttl;
	}
	
	private function writeToFile(): void
	{
		if (!$this->cacheFile)
			return;
		
		if (!file_exists($this->cacheFile))
		{
			if (!touch($this->cacheFile))
			{
				throw new \Exception("Failed to touch {$this->cacheFile}");
			}
		}
		
		echo "writing\n";
		$file = fopen($this->cacheFile, 'c');
		
		try 
		{
			if (!$file)
				throw new \Exception("Failed to open file {$this->cacheFile}");
			
			if (!flock($file, LOCK_EX))
				throw new \Exception("Failed to lock file {$this->cacheFile} for writing");
			
			if (!ftruncate($file, 0))
				throw new \Exception("Failed to truncate file {$this->cacheFile}");
			
			if (!fputs($file, implode(',', [$this->timeout, ...$this->ips])))
				throw new \Exception("Failed to write to file {$this->cacheFile}");
		}
		finally 
		{
			if ($file)
			{
				flock($file, LOCK_UN);
				fclose($file);
			}
		}
	}
	
	private function readFromFile(): bool
	{
		if (!$this->cacheFile || !file_exists($this->cacheFile) || !is_readable($this->cacheFile))
			return false;

		$data = null;
		$file = fopen($this->cacheFile, 'r');
		echo "reading\n";
		try 
		{
			if (!$file)
				return false;
			
			if (!flock($file, LOCK_SH))
				return false;
			
			$data = fgets($file);
		}
		finally 
		{
			if ($file)
			{
				flock($file, LOCK_UN);
				fclose($file);
			}
		}
		
		if (!$data)
			return false;
		
		$data = explode(',', $data);
		
		if (count($data) <= 1 || ((int)($data[0])) <= time())
		{
			return false;
		}
		
		$this->timeout = $data[0];
		unset($data[0]);
		$this->ips = array_values($data);
			
		return true;
	}
	
	private function refreshCache(): void
	{
		if ($this->readFromFile())
		{
			return;
		}
		
		$this->resolve();
		$this->writeToFile();
	}
	
	
	public function __construct(string $host, int $ttl, ?string $cacheDir = null)
	{
		if ($cacheDir)
		{
			$this->cacheFile = Strings::append($cacheDir, '/') . "_gazelle_{$host}_.cache";
		}
		else
		{
			$this->cacheFile = null;
		}
		
		$this->host		= $host;
		$this->ttl		= $ttl;
	}
	
	
	/**
	 * @return string[]
	 */
	public function getAllIPs(): array
	{
		if (!$this->ips || $this->timeout < time())
		{
			$this->refreshCache();
		}
		
		echo $this->timeout . " " . time() . "\n";
		return $this->ips;
	}
	
	public function getRandomIP(): ?string
	{
		$ips = self::getAllIPs();
		
		if (!$ips)
			return null;
		else if (count($ips) == 1)
			return $ips[0];
		
		return $ips[random_int(0, count($ips) - 1)];
	}
}