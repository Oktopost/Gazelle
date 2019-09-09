<?php
namespace Gazelle;


interface IRequestMetaData
{
	public function getStartTime(): float;
	public function getRuntime(): float;
	public function getEndTime(): float;
	
	public function getRedirects(): ?int;
}