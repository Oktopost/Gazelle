<?php
namespace Gazelle;


interface IConnection
{
	public function request(IRequestParams $requestData, IRequestConfig $config): IResponseData;
}