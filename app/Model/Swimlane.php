<?php

namespace Kanboard\Model;

/**
 * Swimlanes
 *
 * @package  model
 * @author   Frederic Guillot
 */
class Swimlane extends Base
{
    /**
     * SQL table name
     *
     * @var string
     */
    const TABLE = 'swimlanes';

    /**
     * Value for active swimlanes
     *
     * @var integer
     */
    const ACTIVE = 1;

    /**
     * Value for inactive swimlanes
     *
     * @var integer
     */
    const INACTIVE = 0;

    /**
     * Get a swimlane by the id
     *
     * @access public
     * @param  integer   $swimlane_id    Swimlane id
     * @return array
     */
    public function getById($swimlane_id)
    {
        return $this->db->table(self::TABLE)->eq('id', $swimlane_id)->findOne();
    }

    /**
     * Get the swimlane name by the id
     *
     * @access public
     * @param  integer   $swimlane_id    Swimlane id
     * @return string
     */
    public function getNameById($swimlane_id)
    {
        return $this->db->table(self::TABLE)->eq('id', $swimlane_id)->findOneColumn('name') ?: '';
    }

    /**
     * Get a swimlane id by the project and the name
     *
     * @access public
     * @param  integer   $project_id      Project id
     * @param  string    $name            Name
     * @return integer
     */
    public function getIdByName($project_id, $name)
    {
        return (int) $this->db->table(self::TABLE)
                        ->eq('project_id', $project_id)
                        ->eq('name', $name)
                        ->findOneColumn('id');
    }

    /**
     * Get a swimlane by the project and the name
     *
     * @access public
     * @param  integer   $project_id      Project id
     * @param  string    $name            Swimlane name
     * @return array
     */
    public function getByName($project_id, $name)
    {
        return $this->db->table(self::TABLE)
                        ->eq('project_id', $project_id)
                        ->eq('name', $name)
                        ->findOne();
    }

    /**
     * Get default swimlane properties
     *
     * @access public
     * @param  integer   $project_id    Project id
     * @return array
     */
    public function getDefault($project_id)
    {
        $result = $this->db->table(Project::TABLE)
                       ->eq('id', $project_id)
                       ->columns('id', 'default_swimlane', 'show_default_swimlane')
                       ->findOne();

        if ($result['default_swimlane'] === 'Default swimlane') {
            $result['default_swimlane'] = t($result['default_swimlane']);
        }

        return $result;
    }

    /**
     * Get all swimlanes for a given project
     *
     * @access public
     * @param  integer   $project_id    Project id
     * @return array
     */
    public function getAll($project_id)
    {
        return $this->db->table(self::TABLE)
                        ->eq('project_id', $project_id)
                        ->orderBy('position', 'asc')
                        ->findAll();
    }

    /**
     * Get the list of swimlanes by status
     *
     * @access public
     * @param  integer   $project_id    Project id
     * @param  integer   $status        Status
     * @return array
     */
    public function getAllByStatus($project_id, $status = self::ACTIVE)
    {
        $query = $this->db->table(self::TABLE)
                        ->eq('project_id', $project_id)
                        ->eq('is_active', $status);

        if ($status == self::ACTIVE) {
            $query->asc('position');
        } else {
            $query->asc('name');
        }

        return $query->findAll();
    }

    /**
     * Get active swimlanes
     *
     * @access public
     * @param  integer   $project_id    Project id
     * @return array
     */
    public function getSwimlanes($project_id)
    {
        $swimlanes = $this->db->table(self::TABLE)
                              ->columns('id', 'name', 'description')
                              ->eq('project_id', $project_id)
                              ->eq('is_active', self::ACTIVE)
                              ->orderBy('position', 'asc')
                              ->findAll();

        $default_swimlane = $this->db->table(Project::TABLE)
                                     ->eq('id', $project_id)
                                     ->eq('show_default_swimlane', 1)
                                     ->findOneColumn('default_swimlane');

        if ($default_swimlane) {
            if ($default_swimlane === 'Default swimlane') {
                $default_swimlane = t($default_swimlane);
            }

            array_unshift($swimlanes, array('id' => 0, 'name' => $default_swimlane));
        }

        return $swimlanes;
    }

    /**
     * Get list of all swimlanes
     *
     * @access public
     * @param  integer   $project_id    Project id
     * @param  boolean   $prepend       Prepend default value
     * @param  boolean   $only_active   Return only active swimlanes
     * @return array
     */
    public function getList($project_id, $prepend = false, $only_active = false)
    {
        $swimlanes = array();
        $default = $this->db->table(Project::TABLE)->eq('id', $project_id)->eq('show_default_swimlane', 1)->findOneColumn('default_swimlane');

        if ($prepend) {
            $swimlanes[-1] = t('All swimlanes');
        }

        if (! empty($default)) {
            $swimlanes[0] = $default === 'Default swimlane' ? t($default) : $default;
        }

        return $swimlanes + $this->db->hashtable(self::TABLE)
                                 ->eq('project_id', $project_id)
                                 ->in('is_active', $only_active ? array(self::ACTIVE) : array(self::ACTIVE, self::INACTIVE))
                                 ->orderBy('position', 'asc')
                                 ->getAll('id', 'name');
    }

    /**
     * Add a new swimlane
     *
     * @access public
     * @param  array    $values   Form values
     * @return integer|boolean
     */
    public function create($values)
    {
        if (! $this->project->exists($values['project_id'])) {
            return 0;
        }
        $values['position'] = $this->getLastPosition($values['project_id']);
        return $this->persist(self::TABLE, $values);
    }

    /**
     * Update a swimlane
     *
     * @access public
     * @param  array    $values    Form values
     * @return bool
     */
    public function update(array $values)
    {
        return $this->db->table(self::TABLE)
                        ->eq('id', $values['id'])
                        ->update($values);
    }

    /**
     * Update the default swimlane
     *
     * @access public
     * @param  array    $values    Form values
     * @return bool
     */
    public function updateDefault(array $values)
    {
        return $this->db
                    ->table(Project::TABLE)
                    ->eq('id', $values['id'])
                    ->update(array(
                        'default_swimlane' => $values['default_swimlane'],
                        'show_default_swimlane' => $values['show_default_swimlane'],
                    ));
    }

    /**
     * Get the last position of a swimlane
     *
     * @access public
     * @param  integer   $project_id
     * @return integer
     */
    public function getLastPosition($project_id)
    {
        return $this->db->table(self::TABLE)
                        ->eq('project_id', $project_id)
                        ->eq('is_active', 1)
                        ->count() + 1;
    }

    /**
     * Disable a swimlane
     *
     * @access public
     * @param  integer   $project_id     Project id
     * @param  integer   $swimlane_id    Swimlane id
     * @return bool
     */
    public function disable($project_id, $swimlane_id)
    {
        $result = $this->db
                    ->table(self::TABLE)
                    ->eq('id', $swimlane_id)
                    ->update(array(
                        'is_active' => self::INACTIVE,
                        'position' => 0,
                    ));

        if ($result) {
            // Re-order positions
            $this->updatePositions($project_id);
        }

        return $result;
    }

    /**
     * Enable a swimlane
     *
     * @access public
     * @param  integer   $project_id     Project id
     * @param  integer   $swimlane_id    Swimlane id
     * @return bool
     */
    public function enable($project_id, $swimlane_id)
    {
        return $this->db
                    ->table(self::TABLE)
                    ->eq('id', $swimlane_id)
                    ->update(array(
                        'is_active' => self::ACTIVE,
                        'position' => $this->getLastPosition($project_id),
                    ));
    }

    /**
     * Remove a swimlane
     *
     * @access public
     * @param  integer   $project_id     Project id
     * @param  integer   $swimlane_id    Swimlane id
     * @return bool
     */
    public function remove($project_id, $swimlane_id)
    {
        $this->db->startTransaction();

        // Tasks should not be assigned anymore to this swimlane
        $this->db->table(Task::TABLE)->eq('swimlane_id', $swimlane_id)->update(array('swimlane_id' => 0));

        if (! $this->db->table(self::TABLE)->eq('id', $swimlane_id)->remove()) {
            $this->db->cancelTransaction();
            return false;
        }

        // Re-order positions
        $this->updatePositions($project_id);

        $this->db->closeTransaction();

        return true;
    }

    /**
     * Update swimlane positions after disabling or removing a swimlane
     *
     * @access public
     * @param  integer  $project_id     Project id
     * @return boolean
     */
    public function updatePositions($project_id)
    {
        $position = 0;
        $swimlanes = $this->db->table(self::TABLE)
                              ->eq('project_id', $project_id)
                              ->eq('is_active', 1)
                              ->asc('position')
                              ->findAllByColumn('id');

        if (! $swimlanes) {
            return false;
        }

        foreach ($swimlanes as $swimlane_id) {
            $this->db->table(self::TABLE)
                     ->eq('id', $swimlane_id)
                     ->update(array('position' => ++$position));
        }

        return true;
    }

    /**
     * Move a swimlane down, increment the position value
     *
     * @access public
     * @param  integer  $project_id     Project id
     * @param  integer  $swimlane_id    Swimlane id
     * @return boolean
     */
    public function moveDown($project_id, $swimlane_id)
    {
        $swimlanes = $this->db->hashtable(self::TABLE)
                              ->eq('project_id', $project_id)
                              ->eq('is_active', self::ACTIVE)
                              ->asc('position')
                              ->getAll('id', 'position');

        $positions = array_flip($swimlanes);

        if (isset($swimlanes[$swimlane_id]) && $swimlanes[$swimlane_id] < count($swimlanes)) {
            $position = ++$swimlanes[$swimlane_id];
            $swimlanes[$positions[$position]]--;

            $this->db->startTransaction();
            $this->db->table(self::TABLE)->eq('id', $swimlane_id)->update(array('position' => $position));
            $this->db->table(self::TABLE)->eq('id', $positions[$position])->update(array('position' => $swimlanes[$positions[$position]]));
            $this->db->closeTransaction();

            return true;
        }

        return false;
    }

    /**
     * Move a swimlane up, decrement the position value
     *
     * @access public
     * @param  integer  $project_id     Project id
     * @param  integer  $swimlane_id    Swimlane id
     * @return boolean
     */
    public function moveUp($project_id, $swimlane_id)
    {
        $swimlanes = $this->db->hashtable(self::TABLE)
                              ->eq('project_id', $project_id)
                              ->eq('is_active', self::ACTIVE)
                              ->asc('position')
                              ->getAll('id', 'position');

        $positions = array_flip($swimlanes);

        if (isset($swimlanes[$swimlane_id]) && $swimlanes[$swimlane_id] > 1) {
            $position = --$swimlanes[$swimlane_id];
            $swimlanes[$positions[$position]]++;

            $this->db->startTransaction();
            $this->db->table(self::TABLE)->eq('id', $swimlane_id)->update(array('position' => $position));
            $this->db->table(self::TABLE)->eq('id', $positions[$position])->update(array('position' => $swimlanes[$positions[$position]]));
            $this->db->closeTransaction();

            return true;
        }

        return false;
    }

    /**
     * Duplicate Swimlane to project
     *
     * @access public
     * @param   integer    $project_from      Project Template
     * @param   integer    $project_to        Project that receives the copy
     * @return  integer|boolean
     */

    public function duplicate($project_from, $project_to)
    {
        $swimlanes = $this->getAll($project_from);

        foreach ($swimlanes as $swimlane) {
            unset($swimlane['id']);
            $swimlane['project_id'] = $project_to;

            if (! $this->db->table(self::TABLE)->save($swimlane)) {
                return false;
            }
        }

        $default_swimlane = $this->getDefault($project_from);
        $default_swimlane['id'] = $project_to;

        $this->updateDefault($default_swimlane);

        return true;
    }
}
