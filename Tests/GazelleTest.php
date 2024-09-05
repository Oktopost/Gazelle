<?php
namespace Gazelle;


use PHPUnit\Framework\TestCase;


class GazelleTest extends TestCase
{
	public function test_multi_sanity(): void
	{
		$g = new Gazelle();
		
		self::assertInstanceOf(MultiGazelle::class, $g->multi());
	}
}