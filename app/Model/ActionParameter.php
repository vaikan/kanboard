<?php

namespace Kanboard\Model;

use SimpleValidator\Validator;
use SimpleValidator\Validators;

/**
 * Action Parameter Model
 *
 * @package  model
 * @author   Frederic Guillot
 */
class ActionParameter extends Base
{
    /**
     * SQL table name
     *
     * @var string
     */
    const TABLE = 'action_has_params';

    /**
     * Get all action params
     *
     * @access public
     * @return array
     */
    public function getAll()
    {
        $params = $this->db->table(self::TABLE)->findAll();
        return $this->toDictionary($params);
    }

    /**
     * Get all params for a list of actions
     *
     * @access public
     * @param  array $action_ids
     * @return array
     */
    public function getAllByActions(array $action_ids)
    {
        $params = $this->db->table(self::TABLE)->in('action_id', $action_ids)->findAll();
        return $this->toDictionary($params);
    }

    /**
     * Build params dictionary
     *
     * @access private
     * @param  array  $params
     * @return array
     */
    private function toDictionary(array $params)
    {
        $result = array();

        foreach ($params as $param) {
            $result[$param['action_id']][$param['name']] = $param['value'];
        }

        return $result;
    }

    /**
     * Get all action params for a given action
     *
     * @access public
     * @param  integer $action_id
     * @return array
     */
    public function getAllByAction($action_id)
    {
        return $this->db->hashtable(self::TABLE)->eq('action_id', $action_id)->getAll('name', 'value');
    }

    /**
     * Insert new parameters for an action
     *
     * @access public
     * @param  integer $action_id
     * @param  array  $values
     * @return boolean
     */
    public function create($action_id, array $values)
    {
        foreach ($values['params'] as $name => $value) {
            $param = array(
                'action_id' => $action_id,
                'name' => $name,
                'value' => $value,
            );

            if (! $this->db->table(self::TABLE)->save($param)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Duplicate action parameters
     *
     * @access public
     * @param  integer  $project_id
     * @param  integer  $action_id
     * @param  array    $params
     * @return boolean
     */
    public function duplicateParameters($project_id, $action_id, array $params)
    {
        foreach ($params as $name => $value) {
            $value = $this->resolveParameter($project_id, $name, $value);

            if ($value === false) {
                $this->logger->error('ActionParameter::duplicateParameters => unable to resolve '.$name.'='.$value);
                return false;
            }

            $values = array(
                'action_id' => $action_id,
                'name' => $name,
                'value' => $value,
            );

            if (! $this->db->table(self::TABLE)->insert($values)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve action parameter values according to another project
     *
     * @access private
     * @param  integer $project_id
     * @param  string  $name
     * @param  string  $value
     * @return mixed
     */
    private function resolveParameter($project_id, $name, $value)
    {
        switch ($name) {
            case 'project_id':
                return $value != $project_id ? $value : false;
            case 'category_id':
                return $this->category->getIdByName($project_id, $this->category->getNameById($value)) ?: false;
            case 'src_column_id':
            case 'dest_column_id':
            case 'dst_column_id':
            case 'column_id':
                $column = $this->board->getColumn($value);
                return empty($column) ? false : $this->board->getColumnIdByTitle($project_id, $column['title']) ?: false;
            case 'user_id':
            case 'owner_id':
                return $this->projectPermission->isAssignable($project_id, $value) ? $value : false;
            default:
                return $value;
        }
    }
}
