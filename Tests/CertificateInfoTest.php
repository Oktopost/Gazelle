<?php
namespace Gazelle\Tests;


use Gazelle\CertificateInfo;
use Gazelle\Exceptions\GazelleException;
use Gazelle\Utils\CertificateInfoQuery;
use PHPUnit\Framework\TestCase;


class CertificateInfoTest extends TestCase
{
	public function testParseAndValidityWindow(): void
	{
		$certificate = CertificateInfo::parse([
			'name' => 'example.test',
			'version' => '3',
			'subject' => ['CN' => 'example.test'],
			'issuer' => ['CN' => 'issuer'],
			'validFrom_time_t' => 100,
			'validTo_time_t' => 200,
		]);

		self::assertSame('example.test', $certificate->Name);
		self::assertSame('3', $certificate->Version);
		self::assertSame(['CN' => 'example.test'], $certificate->Subject);
		self::assertTrue($certificate->isValidAt(150));
		self::assertFalse($certificate->isValidAt(50));
		self::assertFalse($certificate->isValidAt(250));

		$withoutWindow = CertificateInfo::parse([]);
		self::assertTrue($withoutWindow->isValidAt(150));
		self::assertTrue($withoutWindow->isValid());
	}

	public function testCertificateQueryRejectsInvalidInput(): void
	{
		$this->expectException(GazelleException::class);
		CertificateInfoQuery::getCertificateInfo(new \stdClass(), 0);
	}
}
