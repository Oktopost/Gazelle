<?php
namespace Gazelle\Exceptions;


use Gazelle\IResponse;
use Gazelle\IRequestParams;


class GazelleException extends \Exception
{
	public function request(): ?IRequestParams { return null; }
	public function response(): ?IResponse { return null; }
}