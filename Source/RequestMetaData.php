<?php
namespace Gazelle;


class RequestMetaData implements IRequestMetaData
{
	private $endTime;
	private $startTime;
	
	private $redirects	= null;
	
	
	public function __construct(float $startTime, float $endTime)
	{
		$this->startTime = $startTime;
		$this->endTime = $endTime;
	}
	
	public function setRedirects(int $redirects): RequestMetaData
	{
		$this->redirects = $redirects;
		return $this;
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
		return $this->redirects;
	}
}