<?php

namespace App\Controllers;

use Illuminate\Support\Arr;

class LinkController extends BaseController
{
	public function index($request, $response, $args)
	{
		// paging...
		$totalCount = $this->db->getLinksCount();
		$perPage = $this->getSettings('links.per_page');
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
		$baseUrl = $this->linker->links();
		$paging = $this->builder->buildPaging($baseUrl, $totalPages, $page);

		// pics
		$offset = ($page - 1) * $perPage;
		
		$links = $this->db->getLinks($offset, $perPage);
		
    	$params = $this->buildParams([
    	    'sidebar' => [ 'button' ],
			'params' => [
				'title' => $this->getSettings('links.title'),
				'links' => $links,
				'paging' => $paging,
				'disqus_url' => $this->getSettings('view_globals.site_url') . '/links/',
			],
		]);
	
		return $this->view->render($response, 'main/links/index.twig', $params);
	}
}
