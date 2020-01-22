<?php

namespace App\Controllers;

use Illuminate\Support\Arr;

class ComicController extends BaseController
{
	private $comicsTitle;
	
	public function __construct($container)
	{
		parent::__construct($container);

		$this->comicsTitle = $this->getSettings('comics.title');
	}

	public function series($request, $response, $args)
	{
		$alias = $args['alias'];
		
		$row = $this->db->getComicSeriesByAlias($alias);

		if (!$row) {
			return $this->notFound($request, $response);
		}
		
		$id = $row['id'];

		$series = $this->builder->buildComicSeries($row);

		$title = $series['name_ru'];
		if ($series['name_en']) {
			$title .= ' (' . $series['name_en'] . ')';
		}
	
		$comicRows = $this->db->getComicIssues($id);
		
		$comics = [];
		foreach ($comicRows as $comicRow) {
			$comics[] = $this->builder->buildComicIssue($comicRow, $series, true);
		}

		$params = $this->buildParams([
    		'game' => $series['game'],
    		'image' => $this->linker->abs($series['cover_url']),
    		'description' => $series['sub_description'] ?? $series['description'],
			'params' => [
				'series' => $series,
				'comics' => $comics,
				'title' => $title,
				'comics_title' => $this->comicsTitle,
				//'one_column' => true,
			],
		]);

		return $this->view->render($response, 'main/comics/series.twig', $params);
	}
	
	public function issue($request, $response, $args)
	{
		$alias = $args['alias'];
		$number = $args['number'];

		$seriesRow = $this->db->getComicSeriesByAlias($alias);

		if (!$seriesRow) {
			return $this->notFound($request, $response);
		}
		
		$series = $this->builder->buildComicSeries($seriesRow);

		$row = $this->db->getComicIssue(null, $series['id'], $number);
		
		if (!$row) {
			return $this->notFound($request, $response);
		}

		$comic = $this->builder->buildComicIssue($row, $series);
		
		$title = $series['name_ru'] . ' ' . $comic['number_str'];
		if ($series['name_en']) {
			$title .= ' (' . $series['name_en'] . ')';
		}
		
		$pageRows = $this->db->getComicIssuePages($comic['id']);
		
		if ($pageRows != null) {
    		foreach ($pageRows as $pageRow) {
    			$pages[] = $this->builder->buildComicIssuePage($pageRow, $series, $comic);
    		}
		}

		$params = $this->buildParams([
			'game' => $series['game'],
    		'image' => $this->linker->abs($comic['cover_url']),
    		'description' => $comic['description'],
			'params' => [
				'series' => $series,
				'comic' => $comic,
				'pages' => $pages,
				'title' => $title,
				'comics_title' => $this->comicsTitle,
				'rel_prev' => Arr::get($comic, 'prev.page_url'),
				'rel_next' => Arr::get($comic, 'next.page_url'),
				//'one_column' => true,
			],
		]);

		return $this->view->render($response, 'main/comics/issue.twig', $params);
	}
	
	public function standalone($request, $response, $args)
	{
		$alias = $args['alias'];

		$row = $this->db->getComicStandaloneByAlias($alias);

		if (!$row) {
			return $this->notFound($request, $response);
		}

		$comic = $this->builder->buildComicStandalone($row);

		$title = $comic['name_ru'];
		if ($comic['name_en']) {
			$title .= ' (' . $comic['name_en'] . ')';
		}
		
		$pageRows = $this->db->getComicStandalonePages($comic['id']);
		foreach ($pageRows as $pageRow) {
			$pages[] = $this->builder->buildComicStandalonePage($pageRow, $comic);
		}

		$params = $this->buildParams([
			'game' => $comic['game'],
    		'image' => $this->linker->abs($comic['cover_url']),
    		'description' => $comic['description'],
			'params' => [
				'comic' => $comic,
				'pages' => $pages,
				'title' => $title,
				'comics_title' => $this->comicsTitle,
				//'one_column' => true,
			],
		]);

		return $this->view->render($response, 'main/comics/standalone.twig', $params);
	}
	
	public function issuePage($request, $response, $args)
	{
		$alias = $args['alias'];
		$comicNumber = $args['number'];
		$pageNumber = $args['page'];

		$seriesRow = $this->db->getComicSeriesByAlias($alias);

		if (!$seriesRow) {
			return $this->notFound($request, $response);
		}
		
		$series = $this->builder->buildComicSeries($seriesRow);
		
		$comicRow = $this->db->getComicIssue(null, $series['id'], $comicNumber);
		
		if (!$comicRow) {
			return $this->notFound($request, $response);
		}

		$comic = $this->builder->buildComicIssue($comicRow, $series);

		$row = $this->db->getComicIssuePage($comic['id'], $pageNumber);
		
		if (!$row) {
			return $this->notFound($request, $response);
		}

		$page = $this->builder->buildComicIssuePage($row, $series, $comic);

		$title = $page['number_str'] . ' - ' . $series['name_ru'] . ' ' . $comic['number_str'];
		if ($series['name_en']) {
			$title .= ' (' . $series['name_en'] . ')';
		}

		$params = $this->buildParams([
		    'game' => $series['game'],
    		'large_image' => $this->linker->abs($page['url']),
			'params' => [
				'series' => $series,
				'comic' => $comic,
				'page' => $page,
				'title' => $title,
				'comics_title' => $this->comicsTitle,
				'rel_prev' => Arr::get($page, 'prev.page_url'),
				'rel_next' => Arr::get($page, 'next.page_url'),
				'one_column' => true,
			],
		]);

		return $this->view->render($response, 'main/comics/issue_page.twig', $params);
	}
	
	public function standalonePage($request, $response, $args)
	{
		$alias = $args['alias'];
		$pageNumber = $args['page'];

		$comicRow = $this->db->getComicStandaloneByAlias($alias);

		if (!$comicRow) {
			return $this->notFound($request, $response);
		}

		$comic = $this->builder->buildComicStandalone($comicRow);

		$row = $this->db->getComicStandalonePage($comic['id'], $pageNumber);

		if (!$row) {
			return $this->notFound($request, $response);
		}
		
		$page = $this->builder->buildComicStandalonePage($row, $comic);

		$title = $page['number_str'] . ' - ' . $comic['name_ru'];
		if ($comic['name_en']) {
			$title .= ' (' . $comic['name_en'] . ')';
		}

		$params = $this->buildParams([
			'game' => $comic['game'],
    		'large_image' => $this->linker->abs($page['url']),
			'params' => [
				'comic' => $comic,
				'page' => $page,
				'title' => $title,
				'comics_title' => $this->comicsTitle,
				'rel_prev' => Arr::get($page, 'prev.page_url'),
				'rel_next' => Arr::get($page, 'next.page_url'),
				'one_column' => true,
			],
		]);

		return $this->view->render($response, 'main/comics/standalone_page.twig', $params);
	}
}
