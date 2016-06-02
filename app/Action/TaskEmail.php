<?php

namespace Kanboard\Action;

use Kanboard\Model\TaskModel;

/**
 * Email a task to someone
 *
 * @package action
 * @author  Frederic Guillot
 */
class TaskEmail extends Base
{
    /**
     * Get automatic action description
     *
     * @access public
     * @return string
     */
    public function getDescription()
    {
        return t('Send a task by email to someone');
    }

    /**
     * Get the list of compatible events
     *
     * @access public
     * @return array
     */
    public function getCompatibleEvents()
    {
        return array(
            TaskModel::EVENT_MOVE_COLUMN,
            TaskModel::EVENT_CLOSE,
        );
    }

    /**
     * Get the required parameter for the action (defined by the user)
     *
     * @access public
     * @return array
     */
    public function getActionRequiredParameters()
    {
        return array(
            'column_id' => t('Column'),
            'user_id' => t('User that will receive the email'),
            'subject' => t('Email subject'),
        );
    }

    /**
     * Get the required parameter for the event
     *
     * @access public
     * @return string[]
     */
    public function getEventRequiredParameters()
    {
        return array(
            'task_id',
            'column_id',
        );
    }

    /**
     * Execute the action (move the task to another column)
     *
     * @access public
     * @param  array   $data   Event data dictionary
     * @return bool            True if the action was executed or false when not executed
     */
    public function doAction(array $data)
    {
        $user = $this->userModel->getById($this->getParam('user_id'));

        if (! empty($user['email'])) {
            $task = $this->taskFinderModel->getDetails($data['task_id']);

            $this->emailClient->send(
                $user['email'],
                $user['name'] ?: $user['username'],
                $this->getParam('subject'),
                $this->template->render('notification/task_create', array('task' => $task, 'application_url' => $this->configModel->get('application_url')))
            );

            return true;
        }

        return false;
    }

    /**
     * Check if the event data meet the action condition
     *
     * @access public
     * @param  array   $data   Event data dictionary
     * @return bool
     */
    public function hasRequiredCondition(array $data)
    {
        return $data['column_id'] == $this->getParam('column_id');
    }
}
