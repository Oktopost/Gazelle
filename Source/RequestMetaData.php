<?php
namespace Gazelle;


class RequestMetaData implements IRequestMetaData
{
	private $endTime;
	private $startTime;
	
	private $all = [];
	
	
	public function __construct(float $startTime, float $endTime)
	{
		$this->startTime = $startTime;
		$this->endTime = $endTime;
		$this->all[CURLINFO_TOTAL_TIME] = $endTime - $startTime;
	}
	
	public function setRedirects(int $redirects): RequestMetaData
	{
		$this->all[CURLINFO_REDIRECT_COUNT] = $redirects;
		return $this;
	}
	
	public function setInfo(int $flag, $value): void
	{
		$this->all[$flag] = $value;
	}
	
	
	public function getStartTime(): float
	{
		return $this->startTime;
	}
	
	public function getRuntime(): float
	{
		return $this->endTime - $this->startTime;
	}
	
	public function getEndTime(): float
	{
		return $this->endTime;
	}
	
	public function getRedirects(): ?int
	{
		return $this->all[CURLINFO_REDIRECT_COUNT] ?? null;
	}
	
	public function getLocalIP(): ?int
	{
		return $this->all[CURLINFO_LOCAL_IP] ?? null;
	}
	
	public function getLocalPort(): ?int
	{
		return $this->all[CURLINFO_LOCAL_PORT] ?? null;
	}
	
	public function getRemoteIP(): ?int
	{
		return $this->all[CURLINFO_PRIMARY_IP] ?? null;
	}
	
	public function getRemotePort(): ?int
	{
		return $this->all[CURLINFO_PRIMARY_PORT] ?? null;
	}
	
	public function getNameLookupTime(): ?float
	{
		return $this->all[CURLINFO_NAMELOOKUP_TIME] ?? null;
	}
	
	public function getConnectionTime(): ?float
	{
		return $this->all[CURLINFO_CONNECT_TIME] ?? null;
	}
	
	public function getTotalTime(): ?float
	{
		return $this->all[CURLINFO_TOTAL_TIME] ?? null;
	}
	
	public function getRedirectsTime(): ?float
	{
		return $this->all[CURLINFO_REDIRECT_TIME] ?? null;
	}
	
	public function getLastURL(): ?string
	{
		return $this->all[CURLINFO_EFFECTIVE_URL] ?? null;
	}
	
	/**
	 * @param int $flag
	 * @return mixed|null
	 */
	public function getInfo(int $flag)
	{
		return $this->all[$flag] ?? null;
	}
	
	public function getAllInfo(): array
	{
		return $this->all;
	}
}