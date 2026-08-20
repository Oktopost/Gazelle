<?php
namespace Gazelle\Tests\Support;


use Gazelle\IConnection;
use Gazelle\IRequestParams;
use Gazelle\IResponse;


class DummyConnection implements IConnection
{
	public function request(IRequestParams $requestData): IResponse
	{
		throw new \LogicException('No response configured');
	}
}
