<?php

namespace Kanboard\Controller;

use Kanboard\Model\Swimlane as SwimlaneModel;

/**
 * Swimlanes
 *
 * @package controller
 * @author  Frederic Guillot
 */
class Swimlane extends Base
{
    /**
     * Get the swimlane (common method between actions)
     *
     * @access private
     * @param  integer    $project_id
     * @return array
     */
    private function getSwimlane($project_id)
    {
        $swimlane = $this->swimlane->getById($this->request->getIntegerParam('swimlane_id'));

        if (empty($swimlane)) {
            $this->flash->failure(t('Swimlane not found.'));
            $this->response->redirect($this->helper->url->to('swimlane', 'index', array('project_id' => $project_id)));
        }

        return $swimlane;
    }

    /**
     * List of swimlanes for a given project
     *
     * @access public
     */
    public function index(array $values = array(), array $errors = array())
    {
        $project = $this->getProject();

        $this->response->html($this->projectLayout('swimlane/index', array(
            'default_swimlane' => $this->swimlane->getDefault($project['id']),
            'active_swimlanes' => $this->swimlane->getAllByStatus($project['id'], SwimlaneModel::ACTIVE),
            'inactive_swimlanes' => $this->swimlane->getAllByStatus($project['id'], SwimlaneModel::INACTIVE),
            'values' => $values + array('project_id' => $project['id']),
            'errors' => $errors,
            'project' => $project,
            'title' => t('Swimlanes')
        )));
    }

    /**
     * Validate and save a new swimlane
     *
     * @access public
     */
    public function save()
    {
        $project = $this->getProject();
        $values = $this->request->getValues();
        list($valid, $errors) = $this->swimlaneValidator->validateCreation($values);

        if ($valid) {
            if ($this->swimlane->create($values)) {
                $this->flash->success(t('Your swimlane have been created successfully.'));
                $this->response->redirect($this->helper->url->to('swimlane', 'index', array('project_id' => $project['id'])));
            } else {
                $this->flash->failure(t('Unable to create your swimlane.'));
            }
        }

        $this->index($values, $errors);
    }

    /**
     * Change the default swimlane
     *
     * @access public
     */
    public function change()
    {
        $project = $this->getProject();

        $values = $this->request->getValues() + array('show_default_swimlane' => 0);
        list($valid, ) = $this->swimlaneValidator->validateDefaultModification($values);

        if ($valid) {
            if ($this->swimlane->updateDefault($values)) {
                $this->flash->success(t('The default swimlane have been updated successfully.'));
                $this->response->redirect($this->helper->url->to('swimlane', 'index', array('project_id' => $project['id'])));
            } else {
                $this->flash->failure(t('Unable to update this swimlane.'));
            }
        }

        $this->index();
    }

    /**
     * Edit a swimlane (display the form)
     *
     * @access public
     */
    public function edit(array $values = array(), array $errors = array())
    {
        $project = $this->getProject();
        $swimlane = $this->getSwimlane($project['id']);

        $this->response->html($this->projectLayout('swimlane/edit', array(
            'values' => empty($values) ? $swimlane : $values,
            'errors' => $errors,
            'project' => $project,
            'title' => t('Swimlanes')
        )));
    }

    /**
     * Edit a swimlane (validate the form and update the database)
     *
     * @access public
     */
    public function update()
    {
        $project = $this->getProject();

        $values = $this->request->getValues();
        list($valid, $errors) = $this->swimlaneValidator->validateModification($values);

        if ($valid) {
            if ($this->swimlane->update($values)) {
                $this->flash->success(t('Swimlane updated successfully.'));
                $this->response->redirect($this->helper->url->to('swimlane', 'index', array('project_id' => $project['id'])));
            } else {
                $this->flash->failure(t('Unable to update this swimlane.'));
            }
        }

        $this->edit($values, $errors);
    }

    /**
     * Confirmation dialog before removing a swimlane
     *
     * @access public
     */
    public function confirm()
    {
        $project = $this->getProject();
        $swimlane = $this->getSwimlane($project['id']);

        $this->response->html($this->projectLayout('swimlane/remove', array(
            'project' => $project,
            'swimlane' => $swimlane,
            'title' => t('Remove a swimlane')
        )));
    }

    /**
     * Remove a swimlane
     *
     * @access public
     */
    public function remove()
    {
        $this->checkCSRFParam();
        $project = $this->getProject();
        $swimlane_id = $this->request->getIntegerParam('swimlane_id');

        if ($this->swimlane->remove($project['id'], $swimlane_id)) {
            $this->flash->success(t('Swimlane removed successfully.'));
        } else {
            $this->flash->failure(t('Unable to remove this swimlane.'));
        }

        $this->response->redirect($this->helper->url->to('swimlane', 'index', array('project_id' => $project['id'])));
    }

    /**
     * Disable a swimlane
     *
     * @access public
     */
    public function disable()
    {
        $this->checkCSRFParam();
        $project = $this->getProject();
        $swimlane_id = $this->request->getIntegerParam('swimlane_id');

        if ($this->swimlane->disable($project['id'], $swimlane_id)) {
            $this->flash->success(t('Swimlane updated successfully.'));
        } else {
            $this->flash->failure(t('Unable to update this swimlane.'));
        }

        $this->response->redirect($this->helper->url->to('swimlane', 'index', array('project_id' => $project['id'])));
    }

    /**
     * Enable a swimlane
     *
     * @access public
     */
    public function enable()
    {
        $this->checkCSRFParam();
        $project = $this->getProject();
        $swimlane_id = $this->request->getIntegerParam('swimlane_id');

        if ($this->swimlane->enable($project['id'], $swimlane_id)) {
            $this->flash->success(t('Swimlane updated successfully.'));
        } else {
            $this->flash->failure(t('Unable to update this swimlane.'));
        }

        $this->response->redirect($this->helper->url->to('swimlane', 'index', array('project_id' => $project['id'])));
    }

    /**
     * Move up a swimlane
     *
     * @access public
     */
    public function moveup()
    {
        $this->checkCSRFParam();
        $project = $this->getProject();
        $swimlane_id = $this->request->getIntegerParam('swimlane_id');

        $this->swimlane->moveUp($project['id'], $swimlane_id);
        $this->response->redirect($this->helper->url->to('swimlane', 'index', array('project_id' => $project['id'])));
    }

    /**
     * Move down a swimlane
     *
     * @access public
     */
    public function movedown()
    {
        $this->checkCSRFParam();
        $project = $this->getProject();
        $swimlane_id = $this->request->getIntegerParam('swimlane_id');

        $this->swimlane->moveDown($project['id'], $swimlane_id);
        $this->response->redirect($this->helper->url->to('swimlane', 'index', array('project_id' => $project['id'])));
    }
}
