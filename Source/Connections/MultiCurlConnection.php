<?php
namespace Gazelle\Connections;


use Gazelle\IMultiConnection;
use Gazelle\Multi\IMultiResult;


class MultiCurlConnection implements IMultiConnection
{
	private ?\CurlMultiHandle	$multiHandle	= null;
	private array				$options		= [];
	
	
	/** @var \CurlHandle */
	private array $curls = [];
	
	/** @var IMultiResult[] */
	private array $pending = [];
	
	/** @var IMultiResult[] */
	private array $next = [];
	
	
	private function closeCurl(\CurlHandle $curlHandle): void
	{
		curl_multi_remove_handle($this->multiHandle, $curlHandle);
		curl_close($curlHandle);
	}
	
	/**
	 * @param ?\CurlHandle|mixed $handle
	 */
	private function handleResult($handle): void
	{
		
	}
	
	private function initCurl(): void
	{
		if ($this->multiHandle)
			return;
		
		$this->multiHandle = curl_multi_init();
		
		foreach ($this->options as $opt => $val) 
		{
			curl_multi_setopt($this->multiHandle, $opt, $val);
		}
	}
	
	
	public function __destruct()
	{
		$this->close();
	}
	
	
	public function curlMultiHandle(bool $create = false): ?\CurlMultiHandle
	{
		if (!$this->multiHandle && $create)
		{
			$this->initCurl();
		}
		
		return $this->multiHandle;
	}
	
	public function setOptions(array $options): void
	{
		$this->options = $options;
	}
	
	public function getOptions(): array
	{
		return $this->options;
	}
	
	
	public function poll(): bool
	{
		if (!$this->multiHandle)
			return false;
		
		curl_multi_exec($this->multiHandle, $isRunning);
		
		return $isRunning || $this->next;
	}
	
	public function init(IMultiResult $subject): void
	{
		$this->initCurl();
		
		$this->pending[] = $subject;
	}
	
	public function abort(IMultiResult $subject): void
	{
		$inNext = array_search($subject, $this->next, true);
		$inPending = array_search($subject, $this->pending, true);
		
		if ($inNext !== false)
		{
			array_splice($this->next, $inNext, 1);
		}
		else if ($inPending !== false)
		{
			$this->closeCurl($this->curls[$inPending]);
			
			array_splice($this->pending, $inPending, 1);
			array_splice($this->curls, $inPending, 1);
		}
	}
	
	public function next(float $timeout = 0.0): ?IMultiResult
	{
		if (!$this->multiHandle)
		{
			return null;
		}
		
		if (!$this->next)
		{
			curl_multi_select($this->multiHandle, $timeout);
					
			$info = curl_multi_info_read($this->multiHandle);
			
			while ($info)
			{
				$this->handleResult($info['handle'] ?? null);
				$info = curl_multi_info_read($this->multiHandle);
			}
		}
		
		return array_shift($this->next);
	}
	
	public function close(): void
	{
		if (!$this->multiHandle)
			return;
		
		foreach ($this->curls as $curl) 
		{
			$this->closeCurl($curl);
		}
		
		$this->next		= [];
		$this->pending	= [];
		$this->curls	= [];
		
		curl_multi_close($this->multiHandle);
		
		$this->multiHandle = null;
	}
}