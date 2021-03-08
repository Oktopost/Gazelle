<?php
namespace Gazelle\Utils\IP;


use Structura\Strings;


class FileCacheIPProvider extends AbstractIPProviderCache
{
	private string $host;
	private ?string $cacheFile;
	private int $ttl;
	
	
	private function writeToFile(array $ips): void
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
		
		$file = fopen($this->cacheFile, 'c');
		
		try 
		{
			if (!$file)
				throw new \Exception("Failed to open file {$this->cacheFile}");
			
			if (!flock($file, LOCK_EX))
				throw new \Exception("Failed to lock file {$this->cacheFile} for writing");
			
			if (!ftruncate($file, 0))
				throw new \Exception("Failed to truncate file {$this->cacheFile}");
			
			if (!fputs($file, implode(',', [time() + $this->ttl, ...$ips])))
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
	
	private function readFromFile(): ?array
	{
		if (!$this->cacheFile || !file_exists($this->cacheFile) || !is_readable($this->cacheFile))
			return null;

		$data = null;
		$file = fopen($this->cacheFile, 'r');
		
		try 
		{
			if (!$file)
				return null;
			
			if (!flock($file, LOCK_SH))
				return null;
			
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
			return null;
		
		$data = explode(',', $data);
		
		if (count($data) <= 1 || ((int)($data[0])) <= time())
		{
			return null;
		}
		
		unset($data[0]);
		return array_values($data);
	}
	
	
	public function __construct(string $host, int $ttl, ?string $cacheDir = null)
	{
		parent::__construct();
		
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
	protected function doGetAllIPs(): array
	{
		$result = $this->readFromFile();
		
		if ($result)
		{
			return $result;
		}
		
		$result = $this->getParent()->getAllIPs();
		
		if ($result)
		{
			$this->writeToFile($result);
		}
		
		return $result;
	}
}