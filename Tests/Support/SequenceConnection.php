<?php
namespace Gazelle\Tests\Support;


use Gazelle\IConnection;
use Gazelle\IRequestParams;
use Gazelle\IResponse;


class SequenceConnection implements IConnection
{
	/** @var array<IResponse|\Throwable|callable> */
	private array $results;

	/** @var IRequestParams[] */
	public array $requests = [];


	public function __construct(...$results)
	{
		$this->results = $results;
	}

	public function request(IRequestParams $requestData): IResponse
	{
		$this->requests[] = clone $requestData;
		$result = array_shift($this->results);

		if ($result instanceof \Throwable)
		{
			throw $result;
		}

		if (is_callable($result))
		{
			$result = $result($requestData);
		}

		return $result;
	}
}
