<?php

namespace App\Core;

use Plasticode\Core\Linker as LinkerBase;

class Linker extends LinkerBase
{
	public function game($game)
	{
		$params = [
		    'alias' => $game['alias'],
		];

		return $this->router->pathFor('main.game', $params);
	}

	public function news($id)
	{
		return $this->router->pathFor('main.news', [ 'id' => $id ]);
	}
	
	// strips
	public function strips()
	{
	    return $this->router->pathFor('main.strips');
	}
	
	public function stripImg($strip)
	{
		$ext = $this->getExtension($strip['type']);
		return $this->getSettings('folders.strips_public') . $strip['id'] . '.' . $ext;
	}
	
	public function stripThumbImg($strip)
	{
		$ext = $this->getExtension($strip['type']);
		return $this->getSettings('folders.strips_thumbs_public') . $strip['id'] . '.' . $ext;
	}
	
	public function strip($id)
	{
		return $this->router->pathFor('main.strip', [ 'id' => $id ]);
	}
	
	// comics
	public function comicSeries($alias)
	{
		return $this->router->pathFor('main.comics.series', [ 'alias' => $alias ]);
	}

	public function comicIssue($alias, $comicNumber)
	{
		return $this->router->pathFor('main.comics.issue', [ 'alias' => $alias, 'number' => $comicNumber ]);
	}

	public function comicIssuePage($alias, $comicNumber, $pageNumber)
	{
		return $this->router->pathFor('main.comics.issue.page', [
			'alias' => $alias,
			'number' => $comicNumber,
			'page' => $pageNumber,
		]);
	}

	public function comicStandalone($alias)
	{
		return $this->router->pathFor('main.comics.standalone', [ 'alias' => $alias ]);
	}

	public function comicStandalonePage($alias, $pageNumber)
	{
		return $this->router->pathFor('main.comics.standalone.page', [
			'alias' => $alias,
			'page' => $pageNumber,
		]);
	}

	public function comicPageImg($page)
	{
		$ext = $this->getExtension($page['type']);
		return $this->getSettings('folders.comics_pages_public') . $page['id'] . '.' . $ext;
	}
	
	public function comicThumbImg($page)
	{
		$ext = $this->getExtension($page['type']);
		return $this->getSettings('folders.comics_thumbs_public') . $page['id'] . '.' . $ext;
	}
	
	// links
	public function links()
	{
	    return $this->router->pathFor('main.links');
	}
}
