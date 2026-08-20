<?php
namespace Gazelle\Tests;


use Gazelle\RequestMetaData;
use PHPUnit\Framework\TestCase;


class RequestMetaDataTest extends TestCase
{
	public function testKnownAndCustomInfo(): void
	{
		$meta = new RequestMetaData(10.0, 12.5);
		$meta->setRedirects(2);
		$meta->setInfo(CURLINFO_LOCAL_IP, 127001);
		$meta->setInfo(CURLINFO_LOCAL_PORT, 1234);
		$meta->setInfo(CURLINFO_PRIMARY_IP, 100001);
		$meta->setInfo(CURLINFO_PRIMARY_PORT, 443);
		$meta->setInfo(CURLINFO_NAMELOOKUP_TIME, 0.1);
		$meta->setInfo(CURLINFO_CONNECT_TIME, 0.2);
		$meta->setInfo(CURLINFO_REDIRECT_TIME, 0.3);
		$meta->setInfo(CURLINFO_EFFECTIVE_URL, 'https://example.test');

		self::assertSame(10.0, $meta->getStartTime());
		self::assertSame(12.5, $meta->getEndTime());
		self::assertSame(2.5, $meta->getRuntime());
		self::assertSame(2.5, $meta->getTotalTime());
		self::assertSame(2, $meta->getRedirects());
		self::assertSame(127001, $meta->getLocalIP());
		self::assertSame(1234, $meta->getLocalPort());
		self::assertSame(100001, $meta->getRemoteIP());
		self::assertSame(443, $meta->getRemotePort());
		self::assertSame(0.1, $meta->getNameLookupTime());
		self::assertSame(0.2, $meta->getConnectionTime());
		self::assertSame(0.3, $meta->getRedirectsTime());
		self::assertSame('https://example.test', $meta->getLastURL());
		self::assertNull($meta->getInfo(-1));
		self::assertNotEmpty($meta->getAllInfo());
	}
}
