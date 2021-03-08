<?php
namespace Gazelle\DNS;


class DNSResolver
{
	private ?string $target;
	private ?array $source;
	
	private bool $onlyValid = true;
	private array $dataFilters = [];
	private array $recordFilters = []; 
	
	
	private function getData(): array
	{
		if (!is_null($this->source))
		{
			return $this->source;
		}
		else
		{
			return (@dns_get_record($this->target)) ?: [];
		}
	}
	
	private function getCallback(array $for): ?callable
	{
		if (!$for)
			return null;
		
		return function ($record)
			use ($for)
		{
			foreach ($for as $filter)
			{
				if (!$filter($record))
				{
					return false;
				}
			}
			
			return true;
		};
	}


	/**
	 * @param string|array $target
	 */
	public function __construct($target)
	{
		if (is_string($target))
		{
			$this->target = $target;
			$this->source = null;
		}
		else if (is_array($target))
		{
			$this->target = null;
			$this->source = $target;
		}
		else
		{
			throw new \Exception('Array or string expected');
		}
	}
	
	
	public function matchingDataFilter(callable $filter): DNSResolver
	{
		$this->dataFilters[] = $filter;
		return $this;
	}
	
	public function matchingRecordFilter(callable $filter): DNSResolver
	{
		$this->recordFilters[] = $filter;
		return $this;
	}

	/**
	 * @param string[]|string|int $type
	 * @return DNSResolver
	 */
	public function matchingType($type): DNSResolver
	{
		$type = DNSRecordType::getType($type);
		
		return $this->matchingDataFilter(
			function (array $record)
				use ($type): bool
			{
				$recordType = ($record['type'] ?? '');
				
				if (is_array($type))
				{
					return in_array($recordType, $type);
				}
				else
				{
					return $recordType === $type;
				} 
			});
	}

	public function skipValidation(): DNSResolver
	{
		$this->onlyValid = false;
		return $this;
	}

	/**
	 * @return DNSRecord[]
	 */
	public function query(): array
	{
		$data = $this->getData();
		
		if (!$data) 
			return[];
		
		$records = DNSRecordFactory::parseRecordsWhere(
			$data,
			$this->getCallback($this->dataFilters),
			$this->getCallback($this->recordFilters)
		);
		
		if ($this->onlyValid)
		{
			$records = DNSRecord::filterValid($records);
		}
		
		return $records;
	}
	
	
	/**
	 * @param string|array $target
	 * @return DNSResolver
	 */
	public static function for($target): DNSResolver
	{
		return new DNSResolver($target);
	}

	/**
	 * @param string $target
	 * @return DNSRecord[]
	 */
	public static function queryAll(string $target): array
	{
		return self::for($target)->query();
	}

	/**
	 * @param string $target
	 * @return ARecord[]
	 */
	public static function queryARecords(string $target): array
	{
		return self::for($target)->matchingType(DNS_A)->query();
	}

	/**
	 * @param string $target
	 * @return CNAMERecord[]
	 */
	public static function queryCNAMERecords(string $target): array
	{
		return self::for($target)->matchingType(DNS_CNAME)->query();
	}
}