<?php

namespace App\Controllers;

use Illuminate\Support\Arr;

class StripController extends BaseController
{
	private $stripsTitle;
	
	public function __construct($container)
	{
		parent::__construct($container);

		$this->stripsTitle = $this->getSettings('strips.title');
	}

	public function index($request, $response, $args)
	{
		// paging...
		$totalCount = $this->db->getStripsCount();
		$perPage = $this->getSettings('strips.per_page');
		$totalPages = ceil($totalCount / $perPage);
	
		// determine page
		$page = $request->getQueryParam('page', 1);
		
		if (!is_numeric($page) || $page < 2) {
			$page = 1;
		}
	
		if ($page > $totalPages) {
			$page = $totalPages;
		}
		
		// paging itself
		$baseUrl = $this->linker->strips();
		$paging = $this->builder->buildPaging($baseUrl, $totalPages, $page);

		// pics
		$offset = ($page - 1) * $perPage;
		
		$strips = $this->builder->buildStrips(null, $offset, $perPage);
		
    	$params = $this->buildParams([
			'params' => [
				'title' => $this->stripsTitle,
				'strips' => $strips,
				'paging' => $paging,
				'disqus_url' => $this->getSettings('view_globals.site_url') . '/strips/',
			],
		]);
	
		return $this->view->render($response, 'main/strips/index.twig', $params);
	}

	public function item($request, $response, $args)
	{
		$id = $args['id'];

		$row = $this->db->getStrip($id);
		
		if (!$row) {
			return $this->notFound($request, $response);
		}

		$strip = $this->builder->buildStrip($row);

		$params = $this->buildParams([
			//'game' => $strip['game'],
			'large_image' => $this->linker->abs($strip['url']),
			'params' => [
				'strip' => $strip,
				'title' => $strip['title'],
				'strips_title' => $this->stripsTitle,
				'rel_prev' => Arr::get($strip, 'prev.page_url'),
				'rel_next' => Arr::get($strip, 'next.page_url'),
				'one_column' => true,
			],
		]);

		return $this->view->render($response, 'main/strips/item.twig', $params);
	}
}
