<?php
namespace Gazelle\DNS;


use Traitor\TEnum;


class DNSRecordType
{
	use TEnum;

	
	public const A		= 'A';
	public const CNAME	= 'CNAME';


	/**
	 * @param array|string|int $type
	 * @return string|string[]
	 */
	public static function getType($type)
	{
		if (is_array($type))
		{
			$data = [];
			
			foreach ($type as $item)
			{
				$data[] = self::getType($item);
			}
			
			return $data;
		}
		
		if (is_string($type))
			return $type;
		
		switch ($type)
		{
			case DNS_A:
				return self::A;
				
			case DNS_CNAME:
				return self::CNAME;
			
			default:
				return (string)$type;
		}
	}
}