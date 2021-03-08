<?php
namespace Gazelle\DNS;


use Objection\LiteObject;
use Objection\LiteSetup;


/**
 * @property array			$OriginalRecord
 * @property string|null	$Host
 * @property string|null	$Type
 * @property int|null		$TimeToLive
 */
class DNSRecord extends LiteObject
{
	protected function _setup()
	{
		return [
			'OriginalRecord'	=> LiteSetup::createArray(),
			'Host'				=> LiteSetup::createString(null),
			'Type'				=> LiteSetup::createString(null),
			'TimeToLive'		=> LiteSetup::createInt(null)
		];
	}
	
	
	public function parse(array $data): void
	{
		$this->OriginalRecord	= $data;
		$this->Host				= $data['host'] ?? null;
		$this->Type				= $data['type'] ?? null;
		$this->TimeToLive		= $data['ttl'] ?? null;
	}
	
	public function isValid(): bool
	{
		return 
			!is_null($this->Host) &&
			!is_null($this->Type);
	}
	
	
	/**
	 * @param DNSRecord[] $records
	 * @return DNSRecord[]
	 */
	public static function filterValid(array $records): array
	{
		return array_filter(
			$records, 
			function (DNSRecord $record): bool
			{
				return $record->isValid();
			});
	}
}