<?php
namespace Gazelle\Exceptions;


use Gazelle\IResponseData;
use Gazelle\IRequestParams;


class GazelleException extends \Exception
{
	public function request(): ?IRequestParams { return null; }
	public function response(): ?IResponseData { return null; }
}