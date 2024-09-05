<?php

namespace Gazelle;


use Gazelle\Multi\IMultiConnection;
use PHPUnit\Framework\TestCase;


class GazelleMockTest extends TestCase
{
	public function test_reset_sanity()
	{
		GazelleMock::$multiConnection	= $this->createMock(IMultiConnection::class);
		GazelleMock::$connection		= $this->createMock(IConnection::class);
		
		
		GazelleMock::reset();
		
		
		self::assertNull(GazelleMock::$multiConnection);
		self::assertNull(GazelleMock::$connection);
	}
}
