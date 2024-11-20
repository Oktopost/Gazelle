<?php

namespace Gazelle\Connections;


use Gazelle\Multi\IMultiExecutor;
use Gazelle\Multi\MultiRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


class MultiCurlConnectionTest extends TestCase
{
	private function mockMultiExecutor(): MockObject & IMultiExecutor
	{
		return $this->createMock(IMultiExecutor::class);
	}
	
	
	public function test_isRunning_NewObject_ReturnFalse(): void
	{
		$g = new MultiCurlConnection();
		
		self::assertFalse($g->isRunning());
	}
	
	public function test_isRunning_NewRequestAdded_ReturnFalse(): void
	{
		$e = $this->mockMultiExecutor();
		$g = new MultiCurlConnection();
		$m = new MultiRequest($e);
		
		$m->setURL("http://127.0.0.1:80/")
			->setConnectionTimeout(0.001);
		
		
		$g->send($m, $e);
		
		
		self::assertTrue($g->isRunning());
	}
}
