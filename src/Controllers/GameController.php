<?php

namespace App\Controllers;

use Plasticode\Util\Arrays;

class GameController extends BaseController
{
	public function index($request, $response, $args)
	{
		$alias = $args['alias'];
		
		$game = $this->db->getGameByAlias($alias);
		
		if (!$game) {
			return $this->notFound($request, $response);
		}
		
		$series = $this->builder->buildSortedComicSeries($game);
		$standalones = $this->builder->buildSortedComicStandalones($game);
		$strips = $this->builder->buildStrips($game);
		
		$image = !empty($series)
		    ? Arrays::first($series)['cover_url']
		    : (!empty($standalones)
		        ? Arrays::first($standalones)['cover_url']
		        : (!empty($strips)
		            ? Arrays::first($strips)['url']
		            : null));
		
		$params = $this->buildParams([
		    'game' => $game,
		    'image' => $image ? $this->linker->abs($image) : null,
			'params' => [
				'series' => $series,
				'standalones' => $standalones,
				'strips' => $strips,
			    'title' => $game['name'],
				'disqus_url' => $this->getSettings('view_globals.site_url') . '/' . $game['alias'] . '/',
				//'one_column' => true,
			],
		]);
	
		return $this->view->render($response, 'main/games/item.twig', $params);
	}
}
