<?php
namespace Gazelle\Exceptions\Utils;


use Gazelle\IResponseData;


trait TRequestException
{
	public function __construct(IResponseData $data)
	{
		/** @noinspection PhpUndefinedClassInspection */
		/** @noinspection PhpUndefinedMethodInspection */
		parent::__construct($data, "{$data->getCode()}: {$this->getErrorMessage()}");
		$this->response = $data;
	}
}