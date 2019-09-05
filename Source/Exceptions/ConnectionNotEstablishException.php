<?php
namespace Gazelle\Exceptions;


use Gazelle\IRequestSettings;
use Gazelle\IRequestConfig;


class ConnectionNotEstablishException extends GazelleException
{
	/** @var IRequestSettings */
	private $request;
	
	/** @var IRequestConfig */
	private $config;
	
	
	public function __construct(IRequestSettings $request, IRequestConfig $config, string $message)
	{
		parent::__construct($message);
	}
	
	
	public function requestData(): IRequestSettings
	{
		return $this->request;
	}
	
	public function requestConfig(): IRequestConfig
	{
		return $this->config;
	}
}