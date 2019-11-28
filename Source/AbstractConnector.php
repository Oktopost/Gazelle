<?php
namespace Gazelle;


use Structura\URL;
use Structura\Strings;


abstract class AbstractConnector
{
	/** @var Gazelle */
	private $gazelle;
	
	
	protected function getGazelle(): Gazelle
	{
		return $this->gazelle;
	}
	
	protected function getTemplate(): IRequestParams
	{
		return $this->gazelle->template();
	}
	
	protected function request($path = null, ?array $params = null, ?array $headers = null, $body = null): Request
	{
		$request = $this->gazelle->request();
		
		if ($path)
		{
			$url = new URL($path);
			
			if ($url->Scheme)
			{
				$request->setURL($path);
			}
			else 
			{
				$currentURL = $request->getURL();
				$currentPath = $currentURL->Path ?? '';
				
				$currentPath	= Strings::endWith($currentPath, '/');
				$path			= Strings::trimStart($path, '/');
				
				$currentURL->Path = $currentPath . $path;
			}
		}
		
		if ($params)
		{
			$request->setQueryParams($params);
		}
		
		if ($headers)
		{
			$request->setHeaders($headers);
		}
		
		if ($body)
		{
			$request->setBody($body);
		}
		
		return $request;
	}
	
	
	protected function getDefaultTags(): array { return []; }
	protected function setupGazelle(Gazelle $gazelle): void {}
	protected function setupTemplate(IRequestParams $template): void {}
	
	/**
	 * @return IConnectionDecorator[]
	 */
	protected function setupDecorators(): array 
	{ 
		return []; 
	}
	
	
	public function __construct()
	{
		$this->gazelle = new Gazelle();
		$template = $this->gazelle->template();
		
		$this->setupGazelle($this->gazelle);
		$this->setupTemplate($template);
		$template->addTags($this->getDefaultTags());
		
		$decorators = $this->setupDecorators();
		
		if ($this->setupDecorators())
		{
			$this->gazelle->addDecorator($decorators);
		}
	}
}