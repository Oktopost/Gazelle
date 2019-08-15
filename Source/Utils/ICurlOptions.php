<?php
namespace Gazelle\Utils;


interface ICurlOptions
{
	public function toCurlOptions(): array;
}