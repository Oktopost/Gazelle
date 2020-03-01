<?php
namespace Gazelle;


interface IConnection
{
	public function request(IRequestParams $requestData): IResponse;
}