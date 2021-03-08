<?php
namespace Gazelle\Utils\IP;


interface IIPProvider
{
	public function getAllIPs(): array;
	public function getRandomIP(): ?string;
}