<?php

namespace Kanboard\Model;

use SimpleValidator\Validator;
use SimpleValidator\Validators;

/**
 * Action Model
 *
 * @package  model
 * @author   Frederic Guillot
 */
class Action extends Base
{
    /**
     * SQL table name for actions
     *
     * @var string
     */
    const TABLE = 'actions';

    /**
     * Return actions and parameters for a given user
     *
     * @access public
     * @param  integer $user_id
     * @return array
     */
    public function getAllByUser($user_id)
    {
        $project_ids = $this->projectPermission->getActiveProjectIds($user_id);
        $actions = array();

        if (! empty($project_ids)) {
            $actions = $this->db->table(self::TABLE)->in('project_id', $project_ids)->findAll();
            $params = $this->actionParameter->getAllByActions(array_column($actions, 'id'));
            $this->attachParamsToActions($actions, $params);
        }

        return $actions;
    }

    /**
     * Return actions and parameters for a given project
     *
     * @access public
     * @param  integer $project_id
     * @return array
     */
    public function getAllByProject($project_id)
    {
        $actions = $this->db->table(self::TABLE)->eq('project_id', $project_id)->findAll();
        $params = $this->actionParameter->getAllByActions(array_column($actions, 'id'));
        return $this->attachParamsToActions($actions, $params);
    }

    /**
     * Return all actions and parameters
     *
     * @access public
     * @return array
     */
    public function getAll()
    {
        $actions = $this->db->table(self::TABLE)->findAll();
        $params = $this->actionParameter->getAll();
        return $this->attachParamsToActions($actions, $params);
    }

    /**
     * Fetch an action
     *
     * @access public
     * @param  integer $action_id
     * @return array
     */
    public function getById($action_id)
    {
        $action = $this->db->table(self::TABLE)->eq('id', $action_id)->findOne();

        if (! empty($action)) {
            $action['params'] = $this->actionParameter->getAllByAction($action_id);
        }

        return $action;
    }

    /**
     * Attach parameters to actions
     *
     * @access private
     * @param  array  &$actions
     * @param  array  &$params
     * @return array
     */
    private function attachParamsToActions(array &$actions, array &$params)
    {
        foreach ($actions as &$action) {
            $action['params'] = isset($params[$action['id']]) ? $params[$action['id']] : array();
        }

        return $actions;
    }

    /**
     * Remove an action
     *
     * @access public
     * @param  integer $action_id
     * @return bool
     */
    public function remove($action_id)
    {
        return $this->db->table(self::TABLE)->eq('id', $action_id)->remove();
    }

    /**
     * Create an action
     *
     * @access public
     * @param  array   $values  Required parameters to save an action
     * @return boolean|integer
     */
    public function create(array $values)
    {
        $this->db->startTransaction();

        $action = array(
            'project_id' => $values['project_id'],
            'event_name' => $values['event_name'],
            'action_name' => $values['action_name'],
        );

        if (! $this->db->table(self::TABLE)->insert($action)) {
            $this->db->cancelTransaction();
            return false;
        }

        $action_id = $this->db->getLastId();

        if (! $this->actionParameter->create($action_id, $values)) {
            $this->db->cancelTransaction();
            return false;
        }

        $this->db->closeTransaction();

        return $action_id;
    }

    /**
     * Copy actions from a project to another one (skip actions that cannot resolve parameters)
     *
     * @author Antonio Rabelo
     * @param  integer    $src_project_id      Source project id
     * @return integer    $dst_project_id      Destination project id
     * @return boolean
     */
    public function duplicate($src_project_id, $dst_project_id)
    {
        $actions = $this->action->getAllByProject($src_project_id);

        foreach ($actions as $action) {
            $this->db->startTransaction();

            $values = array(
                'project_id' => $dst_project_id,
                'event_name' => $action['event_name'],
                'action_name' => $action['action_name'],
            );

            if (! $this->db->table(self::TABLE)->insert($values)) {
                $this->db->cancelTransaction();
                continue;
            }

            $action_id = $this->db->getLastId();

            if (! $this->actionParameter->duplicateParameters($dst_project_id, $action_id, $action['params'])) {
                $this->logger->error('Action::duplicate => skip action '.$action['action_name'].' '.$action['id']);
                $this->db->cancelTransaction();
                continue;
            }

            $this->db->closeTransaction();
        }

        return true;
    }

    /**
     * Validate action creation
     *
     * @access public
     * @param  array   $values           Required parameters to save an action
     * @return array   $valid, $errors   [0] = Success or not, [1] = List of errors
     */
    public function validateCreation(array $values)
    {
        $v = new Validator($values, array(
            new Validators\Required('project_id', t('The project id is required')),
            new Validators\Integer('project_id', t('This value must be an integer')),
            new Validators\Required('event_name', t('This value is required')),
            new Validators\Required('action_name', t('This value is required')),
            new Validators\Required('params', t('This value is required')),
        ));

        return array(
            $v->execute(),
            $v->getErrors()
        );
    }
}
