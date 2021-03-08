<?php
namespace Gazelle\Utils\IP;


use Gazelle\DNS\DNSResolver;


class DNSIPProvider extends AbstractIPProvider
{
	private string $host;
	
	
	public function __construct(string $host)
	{
		$this->host = $host;
	}
	
	
	public function getAllIPs(): array
	{
		return DNSResolver::resolveToIPs($this->host);
	}
}