<?php
namespace Gazelle\Utils;


interface IWithCurlOptions
{
	public function toCurlOptions(): array;
}