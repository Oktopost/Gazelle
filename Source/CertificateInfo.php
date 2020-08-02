<?php
namespace Gazelle;


use Objection\LiteObject;
use Objection\LiteSetup;


/**
 * @property string|null	$Name
 * @property array|null		$Subject
 * @property array|null		$Issuer
 * @property string|null	$Version
 * @property string|null	$ValidFrom
 * @property string|null	$ValidTo
 * @property array|null		$OriginalData
 */
class CertificateInfo extends LiteObject
{
	protected function _setup()
	{
		return [
			'Name'			=> LiteSetup::createString(null),
			'Subject'		=> LiteSetup::createArray(),
			'Issuer'		=> LiteSetup::createArray(),
			'Version'		=> LiteSetup::createString(null),
			'ValidFrom'		=> LiteSetup::createString(null),
			'ValidTo'		=> LiteSetup::createString(null),
			'OriginalData'	=> LiteSetup::createArray()
		];
	}
	
	
	public function isValid(): bool
	{
		return $this->isValidAt(now());
	}
	
	public function isValidAt($time): bool
	{
		$time = get_time($time);
		
		if ($this->ValidTo && $this->ValidTo < $time)
			return false;
		
		if ($this->ValidFrom && $this->ValidFrom > $time)
			return false;
		
		return true;
	}
	
	
	public static function parse(array $data): CertificateInfo
	{
		$object = new CertificateInfo();
		
		$object->OriginalData	= $data;
		
		$object->Name		= $data['name'] ?? null;
		$object->Version	= $data['version'] ?? null;
		$object->Subject	= $data['subject'] ?? [];
		$object->Issuer		= $data['issuer'] ?? [];
		
		if (isset($data['validFrom_time_t']))
		{
			$object->ValidFrom = get_time($data['validFrom_time_t']);
		}
		
		if (isset($data['validTo_time_t']))
		{
			$object->ValidTo = get_time($data['validTo_time_t']);
		}
		
		return $object;
	}
}