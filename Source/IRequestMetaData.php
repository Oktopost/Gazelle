<?php
namespace Gazelle;


interface IRequestMetaData
{
	public function getStartTime(): float;
	public function getRuntime(): float;
	public function getEndTime(): float;
	
	public function getRedirects(): ?int;
	public function getLocalIP(): ?int;
	public function getLocalPort(): ?int;
	public function getRemoteIP(): ?int;
	public function getRemotePort(): ?int;
	public function getNameLookupTime(): ?float;
	public function getConnectionTime(): ?float;
	public function getTotalTime(): ?float;
	public function getRedirectsTime(): ?float;
	public function getLastURL(): ?string;
	
	/**
	 * @param int $flag
	 * @return mixed|null
	 */
	public function getInfo(int $flag);
	
	public function getAllInfo(): array;
}