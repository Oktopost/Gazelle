<?php
namespace Gazelle\Exceptions\Response\ClientException;


use Gazelle\IResponse;
use Gazelle\Exceptions\Response\ClientErrorException;


class GenericClientException extends ClientErrorException
{
	private function getErrorMessage(int $code): string
	{
		switch ($code)
		{
			case 406:
				return 'Not Acceptable';
			case 407:
				return 'Proxy Authentication Required';
			case 408:
				return 'Request Timeout';
			case 410:
				return 'Gone';
			case 411:
				return 'Gone';
			case 413:
				return 'Request Entity Too Large';
			case 414:
				return 'Request-URI Too Long';
			case 416:
				return 'Requested Range Not Satisfiable';
			case 417:
				return 'Expectation Failed';
			case 426:
				return 'Upgrade Required';
			
			default:
				return 'Generic Client Exception';
		}
	}
	
	
	public function __construct(IResponse $data)
	{
		parent::__construct($data, "{$data->getCode()}: {$this->getErrorMessage($data->getCode())}");
	}
}