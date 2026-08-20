<?php
namespace Gazelle\Tests\Support;


use Gazelle\Utils\IP\AbstractIPProvider;


class StaticIPProvider extends AbstractIPProvider
{
	public int $calls = 0;


	public function __construct(private array $ips)
	{
	}

	public function getAllIPs(): array
	{
		$this->calls++;
		return $this->ips;
	}
}
