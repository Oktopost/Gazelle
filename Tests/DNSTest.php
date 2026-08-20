<?php
namespace Gazelle\Tests;


use Gazelle\DNS\ARecord;
use Gazelle\DNS\CNAMERecord;
use Gazelle\DNS\DNSRecord;
use Gazelle\DNS\DNSRecordFactory;
use Gazelle\DNS\DNSRecordType;
use Gazelle\DNS\DNSResolver;
use PHPUnit\Framework\TestCase;


class DNSTest extends TestCase
{
	private function records(): array
	{
		return [
			['host' => 'example.test', 'type' => 'A', 'ttl' => 60, 'ip' => '127.0.0.1'],
			['host' => 'example.test', 'type' => 'A', 'ttl' => 60, 'ip' => 'invalid'],
			['host' => 'alias.test', 'type' => 'CNAME', 'ttl' => 60, 'target' => 'example.test'],
			['host' => null, 'type' => 'TXT', 'txt' => 'value'],
		];
	}

	public function testFactoryBuildsSpecializedRecords(): void
	{
		$records = DNSRecordFactory::parseRecords($this->records());
		self::assertInstanceOf(ARecord::class, $records[0]);
		self::assertInstanceOf(CNAMERecord::class, $records[2]);
		self::assertSame('127.0.0.1', $records[0]->IP);
		self::assertSame('example.test', $records[2]->Target);
		self::assertSame($this->records()[0], $records[0]->OriginalRecord);
		self::assertTrue($records[0]->isValid());
		self::assertFalse($records[1]->isValid());
		self::assertFalse($records[3]->isValid());

		self::assertSame(['127.0.0.1'], ARecord::getIPs([$records[0], $records[0]]));
		self::assertCount(2, DNSRecord::filterValid([$records[0], $records[1], $records[2]]));
	}

	public function testFactoryFiltersBeforeAndAfterParsing(): void
	{
		$aRecords = DNSRecordFactory::parseRecordsOfType($this->records(), 'A');
		self::assertCount(2, $aRecords);

		$filtered = DNSRecordFactory::parseRecordsWhere(
			$this->records(),
			fn(array $record): bool => ($record['type'] ?? null) !== 'TXT',
			fn(DNSRecord $record): bool => $record->isValid()
		);
		self::assertCount(2, $filtered);
	}

	public function testResolverUsesInMemoryDataAndFilters(): void
	{
		$records = (new DNSResolver($this->records()))
			->matchingType([DNS_A, DNS_CNAME])
			->matchingDataFilter(fn(array $record): bool => isset($record['host']))
			->matchingRecordFilter(fn(DNSRecord $record): bool => $record->Host === 'example.test')
			->query();

		self::assertCount(1, $records);
		self::assertInstanceOf(ARecord::class, $records[0]);

		$unvalidated = DNSResolver::for($this->records())->skipValidation()->query();
		self::assertCount(4, $unvalidated);
		self::assertSame([], DNSResolver::for([])->query());
	}

	public function testRecordTypeConversion(): void
	{
		self::assertSame('A', DNSRecordType::getType(DNS_A));
		self::assertSame('CNAME', DNSRecordType::getType(DNS_CNAME));
		self::assertSame(['A', 'TXT'], DNSRecordType::getType([DNS_A, 'TXT']));
		self::assertSame('123456', DNSRecordType::getType(123456));
	}

	public function testResolverRejectsUnexpectedInput(): void
	{
		$this->expectException(\Exception::class);
		new DNSResolver(new \stdClass());
	}
}
