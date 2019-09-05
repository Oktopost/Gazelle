<?php
namespace Gazelle;


interface IConnection
{
	public function request(IRequestSettings $requestData, IRequestConfig $config): IResponseData;
}