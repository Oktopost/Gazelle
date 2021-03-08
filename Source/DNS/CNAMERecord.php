<?php
namespace Gazelle\DNS;


use Objection\LiteSetup;

/**
 * @property string|null $Target
 */
class CNAMERecord extends DNSRecord
{
	public function _setup()
	{
		return array_merge(
			parent::_setup(),
			[
				'Target'	=> LiteSetup::createString(null)
			]
		);
	}
	
	
	public function parse(array $data): void
	{
		parent::parse($data);
		$this->Target = $data['target'] ?? null;
	}
	
	public function isValid(): bool
	{
		return 
			parent::isValid() && 
			!is_null($this->Target);
	}
}