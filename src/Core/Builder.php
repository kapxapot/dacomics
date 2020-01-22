<?php

namespace App\Core;

use Plasticode\Core\Builder as BuilderBase;
use Plasticode\Util\Date;

use App\Data\Taggable;

class Builder extends BuilderBase
{
	public function buildGame($row)
	{
		$game = $row;
		
		$game['url'] = $this->linker->game($game);

		return $game;
	}
	
	public function buildFeed()
	{
	    $news = array_map(function ($news) {
		    return $this->buildNews($news);
        }, $this->db->getLatestNews() ?? []);
        
        $comicIssues = array_map(function ($issue) {
            return $this->buildComicIssue($issue, null, true);
        }, $this->db->getComicIssues() ?? []);

        $comicStandalones = array_map(function ($standalone) {
            return $this->buildComicStandalone($standalone);
        }, $this->db->getComicStandalones() ?? []);

        $strips = array_map(function ($strip) {
            return $this->buildStrip($strip);
        }, $this->db->getStrips() ?? []);

	    $parts = [
	        'news' => $news,
	        'comics' => array_merge($comicIssues, $comicStandalones),
	        'strips' => $strips,
        ];

        $feed = [];
        
        foreach ($parts as $type => $items) {
            foreach ($items as $item) {
                $feed[] = [
                    'type' => $type,
                    'date' => strtotime($item['published_at']),
                    'value' => $item,
                ];
            }
        }

		$sorted = $this->sortByDate($feed, 'date');

		return $sorted;
	}
	
	public function buildAllNews($page = 1, $pageSize = 7)
	{
		$offset = ($page - 1) * $pageSize;
		$news = $this->db->getLatestNews($offset, $pageSize);
		
        $news = array_map(function ($n) {
			return $this->buildNews($n);
		}, $news ?? []);
		
		$sorted = $this->sortByDate($news);

		return $sorted;
	}
	
	public function buildNews($news, $full = false, $rebuild = false)
	{
		$id = $news['id'];
		
		if (!$rebuild && strlen($news['cache']) > 0) {
			$text = $news['cache'];
		}
		else {
			$parsed = $this->parser->parse($news['text']);
			$text = $parsed['text'];

			$this->db->saveNewsCache($id, $text);
		}

		$url = $this->linker->news($id);
		$url = $this->linker->abs($url);
		$text = $this->parser->parseCut($text, $url, $full);

		$text = $this->parser->renderLinks($text);
		$text = $this->parser->makeAbsolute($text);

		$news['text'] = $text;
		$news['description'] = $this->trunc($text, 'news.description_limit');
		$news['tags'] = $this->tags($news['tags'], Taggable::NEWS);

		$news = $this->stamps($news);

		$news['published_ui'] = Date::formatUi($news['published_at']);
		$news['url'] = $url;

		return $news;
	}

	public function buildNewsLink($news)
	{
		$id = $news['id'];
		
		$news['game'] = $this->buildGame($news['game_id']);

		$news = $this->stamps($news, true);

		$news['published_ui'] = Date::formatUi($news['published_at']);
		$news['subtitle'] = $news['published_ui'];
		$news['url'] = $this->linker->news($id);

		return $news;
	}
	
	public function buildNewsByTag($tag)
	{
		$news = $this->db->getNewsByTag($tag);

		$news = array_map(function($n) {
			return $this->buildNewsLink($n);
		}, $news ?? []);
		
		return $this->sortByDate($news);
	}
	
	public function buildTagParts($tag)
	{
		$parts = [];
		
		$groups = [
			[
				'id' => 'comics',
				'label' => 'Комиксы',
				'values' => $this->buildAllComicsByTag($tag),
				'component' => 'comics'
			],
			[
				'id' => Taggable::STRIPS,
				'label' => 'Стрипы',
				'values' => $this->buildStripsByTag($tag),
				'component' => 'strips'
			],
			[
				'id' => Taggable::NEWS,
				'label' => 'Новости',
				'values' => $this->buildNewsByTag($tag)
			],
		];

		foreach ($groups as $group) {
			if ($group['values']) {
				$parts[] = $group;
			}
		}

		return $parts;
	}

	
	// COMICS
	
	private function buildAllComicsByTag($tag)
	{
        $comics = array_merge(
			$this->buildComicIssuesByTag($tag) ?? [],
			$this->buildComicSeriesByTag($tag) ?? [],
			$this->buildComicStandalonesByTag($tag) ?? []
		);
		
		$sorts = [
			'issued_on' => [ 'dir' => 'desc', 'type' => 'string' ],
		];

		$comics = $this->sort->multiSort($comics, $sorts);
		
		return $comics;
	}

	public function buildSortedComicSeries($filterByGame = null)
	{
		$rows = $this->db->getAllComicSeries($filterByGame);
		
		if (!$rows) {
		    return null;
		}

		$series = [];
		
		foreach ($rows as $row) {
			$series[] = $this->buildComicSeries($row);
		}
			
		$sorts = [
			'issued_on' => [ 'dir' => 'desc', 'type' => 'string' ],
		];

		$series = $this->sort->multiSort($series, $sorts);

		return $series;
	}

	public function buildComicSeries($row)
	{
		$series = $row;
		
		$series['game'] = $this->db->getGame($series['game_id']);

		$series['page_url'] = $this->linker->comicSeries($series['alias']);

		$comicRows = $this->db->getComicIssues($series['id']);
		$comicCount = count($comicRows);
		
		if ($comicCount > 0) {
			$series['cover_url'] = $this->getComicIssueCover($comicRows[0]['id']);
			$series['issued_on'] = $comicRows[$comicCount - 1]['issued_on'];
			
			$series['sub_description'] = $this->parser->justText($comicRows[0]['description']);
		}
		
		$comics = [];
		foreach ($comicRows as $comicRow) {
			$comics[] = $this->builder->buildComicIssue($comicRow, $series, true);
		}
		
		$series['comics'] = $comics;
		
		$series['comic_count'] = $comicCount;
		$series['comic_count_str'] = $this->cases->caseForNumber('выпуск', $comicCount);

		$series['publisher'] = $this->db->getComicPublisher($series['publisher_id']);
		
		if ($series['name_ru'] == $series['name_en']) {
			$series['name_en'] = null;
		}
		
		$series['description'] = $this->parser->justText($series['description']);
		$series['tags'] = $this->tags($series['tags'], 'comics');
		
		$series['type'] = 'series';

		return $series;
	}

	public function buildSortedComicStandalones($filterByGame = null)
	{
		$rows = $this->db->getComicStandalones($filterByGame);
		
		if (!$rows) {
		    return null;
		}

		$comics = [];
		
		foreach ($rows as $row) {
			$comics[] = $this->buildComicStandalone($row);
		}

		return $comics;
	}

	public function buildComicStandalone($row)
	{
		$comic = $row;
		
		$comic['game'] = $this->db->getGame($comic['game_id']);

		$comic['page_url'] = $this->linker->comicStandalone($comic['alias']);

		$pageRows = $this->db->getComicStandalonePages($comic['id']);
		
		if (count($pageRows) > 0) {
			$pageRow = $pageRows[0];
			$comic['cover_url'] = $this->linker->comicThumbImg($pageRow);
		}

		$comic['publisher'] = $this->db->getComicPublisher($comic['publisher_id']);
		$comic['issued_ui'] = Date::formatUi($comic['issued_on']);

		if ($comic['name_ru'] == $comic['name_en']) {
			$comic['name_en'] = null;
		}
		
		$comic['title'] = $comic['name_ru'];

		$comic['description'] = $this->parser->justText($comic['description']);
		$comic['tags'] = $this->tags($comic['tags'], 'comics');
		
		$comic['type'] = 'standalone';

		$comic['published_ui'] = Date::formatUi($comic['published_at']);

		return $comic;
	}
	
	private function padNum($num)
	{
		return str_pad($num, 2, '0', STR_PAD_LEFT);
	}
	
	private function comicNum($comic)
	{
		$numStr = '#' . $comic['number'];
		
		if ($comic['name_ru']) {
			$numStr .= ': ' . $comic['name_ru'];
		}

		return $numStr;
	}
	
	private function pageNum($num)
	{
		return $this->padNum($num);
	}
	
	private function getComicIssueCover($comicId)
	{
		$pageRows = $this->db->getComicIssuePages($comicId);
		
		if (count($pageRows) > 0) {
			$cover = $this->linker->comicThumbImg($pageRows[0]);
		}
		
		return $cover;
	}

	public function buildComicIssue($row, $series = null, $globalContext = null)
	{
		$comic = $row;
		
		if (!$series) {
			$globalContext = $globalContext ?? true;
			
			$seriesRow = $this->db->getComicSeries($comic['series_id']);
			$series = $this->buildComicSeries($seriesRow);
		}
		
		$comic['page_url'] = $this->linker->comicIssue($series['alias'], $comic['number']);
		$comic['cover_url'] = $this->getComicIssueCover($comic['id']);
		$comic['number_str'] = $this->comicNum($comic);
		
		$name = ($globalContext == true)
			? $series['name_ru']
			: 'Выпуск';
		
		$comic['title'] = $name . '&nbsp;' . $comic['number_str'];
		
		$comic['issued_ui'] = Date::formatUi($comic['issued_on']);

		$prev = $this->db->getComicIssuePrev($comic);
		$next = $this->db->getComicIssueNext($comic);
		
		if ($prev != null) {
			$prev['page_url'] = $this->linker->comicIssue($series['alias'], $prev['number']);
			$prev['number_str'] = $this->comicNum($prev);
			$comic['prev'] = $prev;
		}
		
		if ($next != null) {
			$next['page_url'] = $this->linker->comicIssue($series['alias'], $next['number']);
			$next['number_str'] = $this->comicNum($next);
			$comic['next'] = $next;
		}
		
		$comic['description'] = $this->parser->justText($comic['description']);
		$comic['tags'] = $this->tags($comic['tags'], 'comics');
		
		$comic['type'] = 'issue';

		$comic['published_ui'] = Date::formatUi($comic['published_at']);

		return $comic;
	}
	
	public function buildComicIssuePage($row, $series, $comic)
	{
		$page = $row;

		$id = $page['id'];
		
		$page['url'] = $this->linker->comicPageImg($page);
		$page['thumb'] = $this->linker->comicThumbImg($page);
		$page['page_url'] = $this->linker->comicIssuePage($series['alias'], $comic['number'], $page['number']);
		$page['number_str'] = $this->pageNum($page['number']);

		$prev = $this->db->getComicIssuePagePrev($comic, $page);
		$next = $this->db->getComicIssuePageNext($comic, $page);
		
		if ($prev != null) {
			$prev['page_url'] = $this->linker->comicIssuePage($series['alias'], $prev['comic']['number'], $prev['number']);
			$prev['comic_number_str'] = $this->comicNum($prev['comic']);
			$prev['number_str'] = $this->pageNum($prev['number']);
			$page['prev'] = $prev;
		}
		
		if ($next != null) {
			$next['page_url'] = $this->linker->comicIssuePage($series['alias'], $next['comic']['number'], $next['number']);
			$next['comic_number_str'] = $this->comicNum($next['comic']);
			$next['number_str'] = $this->pageNum($next['number']);
			$page['next'] = $next;
		}
		
		$page['ext'] = 'jpg';

		return $page;
	}
	
	public function buildComicStandalonePage($row, $comic)
	{
		$page = $row;

		$id = $page['id'];
		
		$page['url'] = $this->linker->comicPageImg($page);
		$page['thumb'] = $this->linker->comicThumbImg($page);
		$page['page_url'] = $this->linker->comicStandalonePage($comic['alias'], $page['number']);
		$page['number_str'] = $this->pageNum($page['number']);

		$prev = $this->db->getComicStandalonePagePrev($page);
		$next = $this->db->getComicStandalonePageNext($page);
		
		if ($prev != null) {
			$prev['page_url'] = $this->linker->comicStandalonePage($comic['alias'], $prev['number']);
			$prev['number_str'] = $this->pageNum($prev['number']);
			$page['prev'] = $prev;
		}
		
		if ($next != null) {
			$next['page_url'] = $this->linker->comicStandalonePage($comic['alias'], $next['number']);
			$next['number_str'] = $this->pageNum($next['number']);
			$page['next'] = $next;
		}
		
		$page['ext'] = 'jpg';

		return $page;
	}

	public function buildComicSeriesByTag($tag)
	{
		$rows = $this->db->getComicSeriesByTag($tag);

		$series = array_map(function($row) {
			return $this->buildComicSeries($row);
		}, $rows ?? []);

		return $series;
	}

	public function buildComicIssuesByTag($tag)
	{
		$rows = $this->db->getComicIssuesByTag($tag);

		$comics = array_map(function($row) {
			return $this->buildComicIssue($row, true);
		}, $rows ?? []);
		
		return $comics;
	}

	public function buildComicStandalonesByTag($tag)
	{
		$rows = $this->db->getComicStandalonesByTag($tag);

		$comics = array_map(function($row) {
			return $this->buildComicStandalone($row);
		}, $rows ?? []);
		
		return $comics;
	}
	
	// STRIPS
	
	public function buildStrips($filterByGame, $offset = 0, $limit = 0)
    {
        $rows = $this->db->getStrips($filterByGame, $offset, $limit);

        $strips = array_map(function ($strip) {
            return  $this->buildStrip($strip);
        }, $rows ?? []);

        return $strips;
    }
    
    protected function enrichStripTitle($strip)
    {
        if (intval($strip['title']) == $strip['id']) {
            $strip['title'] = 'Стрип #' . $strip['id'];
        }
        
        return $strip;
    }
    
	public function buildStrip($row)
	{
		$strip = $row;

		$id = $strip['id'];

		if ($strip['game_id']) {
		    $game = $this->db->getGame($strip['game_id']);
		    if ($game) {
			    $strip['game'] = $this->buildGame($game);
		    }
		}

		$strip['ext'] = $this->linker->getExtension($strip['type']);
		$strip['page_url'] = $this->linker->strip($id);
		$strip['url'] = $this->linker->stripImg($strip);
		$strip['thumb'] = $this->linker->stripThumbImg($strip);
		$strip['tags'] = $this->tags($strip['tags'], Taggable::STRIPS);
		
		$strip = $this->enrichStripTitle($strip);

		$prev = $this->db->getStripPrev($strip);
		$next = $this->db->getStripNext($strip);

		if ($prev != null) {
			$prev['page_url'] = $this->linker->strip($prev['id']);
			$strip['prev'] = $this->enrichStripTitle($prev);
		}
		
		if ($next != null) {
			$next['page_url'] = $this->linker->strip($next['id']);
			$strip['next'] = $this->enrichStripTitle($next);
		}
		
		$strip['created_ui'] = Date::formatUi($strip['created_at']);

		$strip['published_ui'] = Date::formatUi($strip['published_at']);

		return $this->stamps($strip, true);
	}
	
	public function buildStripsByTag($tag)
	{
		$rows = $this->db->getStripsByTag($tag);

		$strips = array_map(function($row) {
			return $this->buildStrip($row);
		}, $rows ?? []);
		
		return $strips;
	}
}
