<?php

namespace App\Data;

use Plasticode\Data\Db as DbBase;

class Db extends DbBase
{
    public function getGame($id)
	{
	    return $this->get(Tables::GAMES, $id);
	}

	public function getGameByAlias($alias)
	{
		return $this->getByField(Tables::GAMES, 'alias', $alias);
	}
	
	protected function byGame($query, $filterByGame)
	{
		return $query->where('game_id', $filterByGame['id']);
	}
	
	// NEWS

	public function getLatestNews($offset = 0, $limit = 0)
	{
		return $this->getMany(Tables::NEWS, function($query) use ($offset, $limit) {
			$query = $query
				->where('published', 1)
	   			->whereRaw('(published_at < now())');
	
			$query = $query->orderByDesc('published_at');
			
			if ($offset > 0 || $limit > 0) {
				$query = $query
					->offset($offset)
					->limit($limit);
			}

			return $query;
		});
	}
	
	public function getNewsCount()
	{
		$news = $this->getLatestNews();
		
		return count($news);
	}

	public function getNews($id)
	{
		return $this->getProtected(Tables::NEWS, $id);
	}

	public function saveNewsCache($id, $cache)
	{
		$this->setFieldNoStamps(Tables::NEWS, $id, 'cache', $cache);
	}

	public function getNewsByTag($tag)
	{
		return $this->getByTag(Tables::NEWS, Taggable::NEWS, $tag);
	}
	
	// COMICS
	
	public function getComicPublisher($id)
	{
		return $this->get(Tables::COMIC_PUBLISHERS, $id);
	}
	
	public function getAllComicSeries($filterByGame = null)
	{
		return $this->getMany(Tables::COMIC_SERIES, function($q) use ($filterByGame) {
		    if ($filterByGame) {
		        $q = $q->where('game_id', $filterByGame['id']);
		    }
		    
			return $q->where('published', 1);
		});
	}

	public function getComicSeries($id = null, $all = false)
	{
		return $this->getBy(Tables::COMIC_SERIES, function($q) use ($id, $all) {
			$q = $q->where('id', $id);
			
			if (!$all) {
			    $q = $q->where('published', 1);
			}
			
			return $q;
		});
	}

	public function getComicSeriesByAlias($alias)
	{
		return $this->getBy(Tables::COMIC_SERIES, function($q) use ($alias) {
			return $q
				->where('alias', $alias)
				->where('published', 1);
		});
	}
	
	public function getComicStandalones($filterByGame = null)
	{
		return $this->getMany(Tables::COMIC_STANDALONES, function($q) use ($filterByGame) {
		    if ($filterByGame) {
		        $q = $q->where('game_id', $filterByGame['id']);
		    }
		    
			return $q
				->where('published', 1)
				->orderByDesc('issued_on');
		});
	}
	
	public function getComicStandalone($id, $all = false)
	{
		return $this->getBy(Tables::COMIC_STANDALONES, function($q) use ($id, $all) {
			$q = $q->where('id', $id);
			
			if (!$all) {
			    $q = $q->where('published', 1);
			}
			
			return $q;
		});
	}
	
	public function getComicStandaloneByAlias($alias)
	{
		return $this->getBy(Tables::COMIC_STANDALONES, function($q) use ($alias) {
			return $q
				->where('alias', $alias)
				->where('published', 1);
		});
	}
	
	public function getComicIssues($seriesId = null)
	{
		return $this->getMany(Tables::COMIC_ISSUES, function($q) use ($seriesId) {
			if ($seriesId) {
			    $q = $q->where('series_id', $seriesId);
			}
			
			return $q
				->where('published', 1)
				->orderByAsc('number');
		});
	}
	
	public function getComicIssue($id, $seriesId = null, $number = null) {
		if ($id) {
			return $this->get(Tables::COMIC_ISSUES, $id);
		}
		else {
			return $this->getBy(Tables::COMIC_ISSUES, function($q) use ($seriesId, $number) {
				return $q
					->where('series_id', $seriesId)
					->where('number', $number)
					->where('published', 1);
			});
		}
	}

	public function getComicIssuePages($comicId)
	{
		return $this->getMany(Tables::COMIC_PAGES, function($q) use ($comicId) {
			return $q
				->where('comic_issue_id', $comicId)
				->where('published', 1)
				->orderByAsc('number');
		});
	}

	public function getComicIssuePage($comicId, $number)
	{
		return $this->getBy(Tables::COMIC_PAGES, function($q) use ($comicId, $number) {
			return $q
				->where('comic_issue_id', $comicId)
				->where('number', $number)
				->where('published', 1);
		});
	}
	
	public function getComicStandalonePages($comicId)
	{
		return $this->getMany(Tables::COMIC_PAGES, function($q) use ($comicId) {
			return $q
				->where('comic_standalone_id', $comicId)
				->where('published', 1)
				->orderByAsc('number');
		});
	}

	public function getComicStandalonePage($comicId, $number)
	{
		return $this->getBy(Tables::COMIC_PAGES, function($q) use ($comicId, $number) {
			return $q
				->where('comic_standalone_id', $comicId)
				->where('number', $number)
				->where('published', 1);
		});
	}
	
	public function getMaxComicIssueNumber($seriesId, $exceptId = null)
	{
        $comic = $this->getBy(Tables::COMIC_ISSUES, function($q) use ($seriesId, $exceptId) {
            $q = $q->where('series_id', $seriesId);
            
            if ($exceptId) {
                $q->whereNotEqual('id', $exceptId);
            }
            
            return $q->orderByDesc('number');
        });
        
        return $comic ? $comic['number'] : 0;
	}
	
	public function getMaxComicPageNumber($context, $exceptId = null)
	{
        $page = $this->getBy(Tables::COMIC_PAGES, function($q) use ($context, $exceptId) {
            foreach ($context as $key => $value) {
                $q = $q->where($key, $value);
            }
            
            if ($exceptId) {
                $q->whereNotEqual('id', $exceptId);
            }
            
            return $q->orderByDesc('number');
        });
        
        return $page ? $page['number'] : 0;
	}

	// generic	
	private function getComicPagePrev($page, $filter)
	{
		return $this->getBy(Tables::COMIC_PAGES, function($q) use ($page, $filter) {
			return $q
				->where($filter, $page[$filter])
				->whereLt('number', $page['number'])
				->where('published', 1)
				->orderByDesc('number');
		});
	}
	
	public function getComicPageNext($page, $filter)
	{
		return $this->getBy(Tables::COMIC_PAGES, function($q) use ($page, $filter) {
			return $q
				->where($filter, $page[$filter])
				->whereGt('number', $page['number'])
				->where('published', 1)
				->orderByAsc('number');
		});
	}

	public function getComicStandalonePagePrev($page)
	{
		return $this->getComicPagePrev($page, 'comic_standalone_id');
	}
	
	public function getComicStandalonePageNext($page)
	{
		return $this->getComicPageNext($page, 'comic_standalone_id');
	}
	
	public function getComicIssuePagePrev($comic, $page)
	{
		$prevPage = $this->getComicPagePrev($page, 'comic_issue_id');
		if ($prevPage) {
			$prevPage['comic'] = $comic;
		}
		else {
			$prevComic = $this->getComicIssuePrev($comic);
			if ($prevComic) {
				$prevComicPages = $this->getComicIssuePages($prevComic['id']);
				$prevPage = array_values(array_slice($prevComicPages, -1))[0];
				$prevPage['comic'] = $prevComic;
			}
		}
		
		return $prevPage;
	}
	
	public function getComicIssuePageNext($comic, $page)
	{
		$nextPage = $this->getComicPageNext($page, 'comic_issue_id');
		if ($nextPage) {
			$nextPage['comic'] = $comic;
		}
		else {
			$nextComic = $this->getComicIssueNext($comic);
			if ($nextComic) {
				$nextComicPages = $this->getComicIssuePages($nextComic['id']);
				$nextPage = $nextComicPages[0];
				$nextPage['comic'] = $nextComic;
			}
		}
		
		return $nextPage;
	}
	
	public function getComicIssuePrev($comic)
	{
		return $this->getBy(Tables::COMIC_ISSUES, function($q) use ($comic) {
			return $q
				->where('series_id', $comic['series_id'])
				->whereLt('number', $comic['number'])
				->where('published', 1)
				->orderByDesc('number');
		});
	}
	
	public function getComicIssueNext($comic)
	{
		return $this->getBy(Tables::COMIC_ISSUES, function($q) use ($comic) {
			return $q
				->where('series_id', $comic['series_id'])
				->whereGt('number', $comic['number'])
				->where('published', 1)
				->orderByAsc('number');
		});
	}
	
	public function getComicSeriesByTag($tag)
	{
		return $this->getByTag(Tables::COMIC_SERIES, Taggable::COMIC_SERIES, $tag, function($q) {
			return $q->orderByAsc('name_ru');
		});
	}
	
	public function getComicIssuesByTag($tag)
	{
		return $this->getByTag(Tables::COMIC_ISSUES, Taggable::COMIC_ISSUES, $tag);
	}
	
	public function getComicStandalonesByTag($tag)
	{
		return $this->getByTag(Tables::COMIC_STANDALONES, Taggable::COMIC_STANDALONES, $tag, function($q) {
			return $q->orderByAsc('name_ru');
		});
	}
	
	// STRIPS

	public function getStrips($filterByGame = null, $offset = 0, $limit = 0)
	{
		return $this->getMany(Tables::STRIPS, function ($q) use ($filterByGame, $offset, $limit) {
			$q = $q->where('published', 1);

		    if ($filterByGame) {
			    $q = $this->byGame($q, $filterByGame);
		    }

			if ($limit > 0) {
				$q = $q
					->offset($offset)
					->limit($limit);
			}

			return $q->orderByDesc('id');
		});
	}
	
	public function getStripsCount($filterByGame = null)
	{
		$strips = $this->getStrips($filterByGame);
		
		return count($strips);
	}
	
	public function getStrip($id)
	{
		return $this->getBy(Tables::STRIPS, function ($q) use ($id) {
			return $q
				->where('id', $id)
				->where('published', 1);
		});
	}
	
	public function getStripsByTag($tag)
	{
		return $this->getByTag(Tables::STRIPS, Taggable::STRIPS, $tag);
	}
	
	public function getStripPrev($strip)
	{
		return $this->getBy(Tables::STRIPS, function ($q) use ($strip) {
			return $q
				->whereLt('id', $strip['id'])
				->where('published', 1)
				->orderByDesc('id');
		});
	}
	
	public function getStripNext($strip)
	{
		return $this->getBy(Tables::STRIPS, function ($q) use ($strip) {
			return $q
				->whereGt('id', $strip['id'])
				->where('published', 1)
				->orderByAsc('id');
		});
	}
	
	// links

	public function getLinks($offset = 0, $limit = 0)
	{
		return $this->getMany(Tables::LINKS, function ($q) use ($offset, $limit) {
			if ($limit > 0) {
				$q = $q
					->offset($offset)
					->limit($limit);
			}

			return $q->orderByAsc('created_at');
		});
	}
	
	public function getLinksCount()
	{
		$links = $this->getLinks();
		
		return count($links);
	}
}
