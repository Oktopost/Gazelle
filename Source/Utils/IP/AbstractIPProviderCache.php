<?php
namespace Gazelle\Utils\IP;


abstract class AbstractIPProviderCache implements IIPProvider
{
	private ?IIPProvider $parent;
	
	
	protected function getParent(): IIPProvider
	{
		return $this->parent;
	}
	
	
	protected abstract function doGetAllIPs(): array;
	protected function doGetRandomIP(): ?string
	{
		$ips = self::getAllIPs();
		
		if (!$ips)
			return null;
		else if (count($ips) == 1)
			return $ips[0];
		
		return $ips[random_int(0, count($ips) - 1)];
	}
	
	
	public function __construct(?IIPProvider $parent = null)
	{
		$this->parent = $parent;
	}
	
	
	public function setParent(IIPProvider $parent): AbstractIPProviderCache
	{
		$this->parent = $parent;
		return $this;
	}
	
	
	public function getAllIPs(): array
	{
		$ips = $this->doGetAllIPs();
		
		if (!$ips && $this->parent)
		{
			return $this->parent->getAllIPs();
		}
		
		return $ips;
	}

	public function getRandomIP(): ?string
	{
		$ips = $this->getRandomIP();
		
		if (!$ips && $this->parent)
		{
			return $this->parent->getRandomIP();
		}
		
		return $ips;
	}
	
	
	public static function createChain(IIPProvider $base, AbstractIPProviderCache ... $chain): IIPProvider
	{
		$current = $base;
		
		foreach ($chain as $item)
		{
			$item->setParent($current);
			$current = $item;
		}
		
		return $current;
	}
}