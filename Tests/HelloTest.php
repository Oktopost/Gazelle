<?php
namespace Gazelle;


use PHPUnit\Framework\TestCase;


class HelloTest extends TestCase
{
	public function testMultiReturnsMultiGazelle(): void
	{
		self::assertInstanceOf(MultiGazelle::class, (new Gazelle())->multi());
	}
}
