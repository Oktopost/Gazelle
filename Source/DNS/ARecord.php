<?php
namespace Gazelle\DNS;


use Objection\LiteSetup;

/**
 * @property string|null $IP
 */
class ARecord extends DNSRecord
{
	public function _setup()
	{
		return array_merge(
			parent::_setup(),
			[
				'IP'	=> LiteSetup::createString(null)
			]
		);
	}
	
	
	public function parse(array $data): void
	{
		parent::parse($data);
		$this->IP = $data['ip'] ?? null;
	}
	
	public function isValid(): bool
	{
		return
			parent::isValid() && 
			filter_var($this->IP, FILTER_VALIDATE_IP);
	}
}