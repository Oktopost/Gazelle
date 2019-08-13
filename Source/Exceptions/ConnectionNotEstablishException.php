<?php
namespace Gazelle\Exceptions;


use Gazelle\IRequestData;
use Gazelle\IRequestConfig;


class ConnectionNotEstablishException extends GazelleException
{
	/** @var IRequestData */
	private $request;
	
	/** @var IRequestConfig */
	private $config;
	
	
	public function __construct(IRequestData $request, IRequestConfig $config, string $message)
	{
		parent::__construct($message);
	}
	
	
	public function requestData(): IRequestData
	{
		return $this->request;
	}
	
	public function requestConfig(): IRequestConfig
	{
		return $this->config;
	}
}