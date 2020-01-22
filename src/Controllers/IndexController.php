<?php

namespace App\Controllers;

class IndexController extends BaseController
{
	public function index($request, $response, $args)
	{
		$page = $request->getQueryParam('page', 1);
		$pageSize = $request->getQueryParam('pagesize', $this->getSettings('news_limit'));
		
		$feed = $this->builder->buildFeed();

        $offset = ($page - 1) * $pageSize;
		$slice = array_slice($feed, $offset, $pageSize);

		// paging
		$count = count($feed);
		$url = $this->router->pathFor('main.index');

		$paging = $this->builder->buildComplexPaging($url, $count, $page, $pageSize);

		$params = $this->buildParams([
			'params' => [
				'feed' => $slice,
				'paging' => $paging,
				'disqus_url' => $this->getSettings('view_globals.site_url'),
			],
		]);
		
		return $this->view->render($response, 'main/news/index.twig', $params);
	}
}
