<?php

namespace Kanboard\Controller;

/**
 * Search controller
 *
 * @package  controller
 * @author   Frederic Guillot
 */
class Search extends Base
{
    public function index()
    {
        $projects = $this->projectUserRole->getProjectsByUser($this->userSession->getId());
        $search = urldecode($this->request->getStringParam('search'));
        $nb_tasks = 0;

        $paginator = $this->paginator
                ->setUrl('search', 'index', array('search' => $search))
                ->setMax(30)
                ->setOrder('tasks.id')
                ->setDirection('DESC');

        if ($search !== '' && ! empty($projects)) {
            $query = $this
                ->taskFilter
                ->search($search)
                ->filterByProjects(array_keys($projects))
                ->getQuery();

            $paginator
                ->setQuery($query)
                ->calculate();

            $nb_tasks = $paginator->getTotal();
        }

        $this->response->html($this->helper->layout->app('search/index', array(
            'values' => array(
                'search' => $search,
                'controller' => 'search',
                'action' => 'index',
            ),
            'paginator' => $paginator,
            'title' => t('Search tasks').($nb_tasks > 0 ? ' ('.$nb_tasks.')' : '')
        )));
    }
}
