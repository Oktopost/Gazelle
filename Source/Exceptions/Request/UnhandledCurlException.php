<?php
namespace Gazelle\Exceptions\Request;


use Gazelle\IRequestParams;
use Gazelle\IRequestConfig;
use Gazelle\Exceptions\RequestException;


class UnhandledCurlException extends RequestException
{
	/**
	 * @param resource $resource
	 * @param IRequestParams $requestData
	 * @param IRequestConfig $config
	 */
	public function __construct($resource, IRequestParams $requestData, IRequestConfig $config)
	{
		parent::__construct($requestData, $config, curl_error($resource), curl_errno($resource));
	}
}