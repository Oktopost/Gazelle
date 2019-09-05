<?php
namespace Gazelle\Exceptions\Request;


use Gazelle\IRequestSettings;
use Gazelle\IRequestConfig;
use Gazelle\Exceptions\RequestException;


class CurlException extends RequestException
{
	/**
	 * @param resource $resource
	 * @param IRequestSettings $requestData
	 * @param IRequestConfig $config
	 */
	public function __construct($resource, IRequestSettings $requestData, IRequestConfig $config)
	{
		parent::__construct($requestData, $config, curl_error($resource), curl_errno($resource));
	}
}