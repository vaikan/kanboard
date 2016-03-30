<?php

namespace Kanboard\Controller;

use Kanboard\Core\Security\Role;

/**
 * Custom Filter management
 *
 * @package controller
 * @author  Timo Litzbarski
 */
class Customfilter extends Base
{
    /**
     * Display list of filters
     *
     * @access public
     */
    public function index(array $values = array(), array $errors = array())
    {
        $project = $this->getProject();

        $this->response->html($this->helper->layout->project('custom_filter/index', array(
            'values' => $values + array('project_id' => $project['id']),
            'errors' => $errors,
            'project' => $project,
            'custom_filters' => $this->customFilter->getAll($project['id'], $this->userSession->getId()),
            'title' => t('Custom filters'),
        )));
    }

    /**
     * Save a new custom filter
     *
     * @access public
     */
    public function save()
    {
        $project = $this->getProject();

        $values = $this->request->getValues();
        $values['user_id'] = $this->userSession->getId();

        list($valid, $errors) = $this->customFilterValidator->validateCreation($values);

        if ($valid) {
            if ($this->customFilter->create($values)) {
                $this->flash->success(t('Your custom filter have been created successfully.'));
                $this->response->redirect($this->helper->url->to('customfilter', 'index', array('project_id' => $project['id'])));
            } else {
                $this->flash->failure(t('Unable to create your custom filter.'));
            }
        }

        $this->index($values, $errors);
    }

    /**
     * Confirmation dialog before removing a custom filter
     *
     * @access public
     */
    public function confirm()
    {
        $project = $this->getProject();
        $filter = $this->customFilter->getById($this->request->getIntegerParam('filter_id'));

        $this->response->html($this->helper->layout->project('custom_filter/remove', array(
            'project' => $project,
            'filter' => $filter,
            'title' => t('Remove a custom filter')
        )));
    }

    /**
     * Remove a custom filter
     *
     * @access public
     */
    public function remove()
    {
        $this->checkCSRFParam();
        $project = $this->getProject();
        $filter = $this->customFilter->getById($this->request->getIntegerParam('filter_id'));

        $this->checkPermission($project, $filter);

        if ($this->customFilter->remove($filter['id'])) {
            $this->flash->success(t('Custom filter removed successfully.'));
        } else {
            $this->flash->failure(t('Unable to remove this custom filter.'));
        }

        $this->response->redirect($this->helper->url->to('customfilter', 'index', array('project_id' => $project['id'])));
    }

    /**
     * Edit a custom filter (display the form)
     *
     * @access public
     */
    public function edit(array $values = array(), array $errors = array())
    {
        $project = $this->getProject();
        $filter = $this->customFilter->getById($this->request->getIntegerParam('filter_id'));

        $this->checkPermission($project, $filter);

        $this->response->html($this->helper->layout->project('custom_filter/edit', array(
            'values' => empty($values) ? $filter : $values,
            'errors' => $errors,
            'project' => $project,
            'filter' => $filter,
            'title' => t('Edit custom filter')
        )));
    }

    /**
     * Edit a custom filter (validate the form and update the database)
     *
     * @access public
     */
    public function update()
    {
        $project = $this->getProject();
        $filter = $this->customFilter->getById($this->request->getIntegerParam('filter_id'));

        $this->checkPermission($project, $filter);

        $values = $this->request->getValues();

        if (! isset($values['is_shared'])) {
            $values += array('is_shared' => 0);
        }

        if (! isset($values['append'])) {
            $values += array('append' => 0);
        }

        list($valid, $errors) = $this->customFilterValidator->validateModification($values);

        if ($valid) {
            if ($this->customFilter->update($values)) {
                $this->flash->success(t('Your custom filter have been updated successfully.'));
                $this->response->redirect($this->helper->url->to('customfilter', 'index', array('project_id' => $project['id'])));
            } else {
                $this->flash->failure(t('Unable to update custom filter.'));
            }
        }

        $this->edit($values, $errors);
    }

    private function checkPermission(array $project, array $filter)
    {
        $user_id = $this->userSession->getId();

        if ($filter['user_id'] != $user_id && ($this->projectUserRole->getUserRole($project['id'], $user_id) === Role::PROJECT_MANAGER || ! $this->userSession->isAdmin())) {
            $this->forbidden();
        }
    }
}
