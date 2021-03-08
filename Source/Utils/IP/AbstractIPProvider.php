<?php
namespace Gazelle\Utils\IP;


abstract class AbstractIPProvider implements IIPProvider
{
	public abstract function getAllIPs(): array;
	
	public function getRandomIP(): ?string
	{
		$ips = $this->getAllIPs();
		
		if (!$ips)
			return null;
		else if (count($ips) == 1)
			return $ips[0];
		
		return $ips[random_int(0, count($ips) - 1)];
	}
}