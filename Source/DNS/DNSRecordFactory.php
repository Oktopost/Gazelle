<?php
namespace Gazelle\DNS;


use Traitor\TStaticClass;


class DNSRecordFactory
{
	use TStaticClass;
	
	
	private const MAP =
	[
		'A'	=> ARecord::class
	];
	
	
	public static function parseRecord(array $data): DNSRecord
	{
		$type = $record['type'] ?? null;
		$class = self::MAP[$type] ?? DNSRecord::class;
		
		$record = new $class();
		$record->parse($data);
		
		return $record;
	}

	/**
	 * @param array $all
	 * @return DNSRecord[]
	 */
	public static function parseRecords(array $all): array
	{
		$records = [];
		
		foreach ($all as $recordData)
		{
			$records[] = self::parseRecord($recordData);
		}
		
		return $records;
	}

	/**
	 * @param array $all
	 * @param callable|null $callbackArray
	 * @param callable|null $callbackObject
	 * @return DNSRecord[]
	 */
	public static function parseRecordsWhere(array $all, ?callable $callbackArray, ?callable $callbackObject = null): array
	{
		$records = [];
		
		foreach ($all as $recordData)
		{
			if ($callbackArray && !$callbackArray($recordData))
			{
				continue;
			}
			
			$record = self::parseRecord($recordData);
			
			if ($callbackObject && !$callbackObject($record))
			{
				continue;
			}
			
			$records[] = $record;
		}
		
		return $records;
	}
	
	/**
	 * @param array $all
	 * @param string $type
	 * @return DNSRecord[]
	 */
	public static function parseRecordsOfType(array $all, string $type): array
	{
		$records = [];
		
		foreach ($all as $recordData)
		{
			if (($recordData['type'] ?? null) !== $type)
				continue;
			
			$records[] = self::parseRecord($recordData);
		}
		
		return $records;
	}
}