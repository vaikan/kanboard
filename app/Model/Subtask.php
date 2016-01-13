<?php

namespace Kanboard\Model;

use PicoDb\Database;
use Kanboard\Event\SubtaskEvent;

/**
 * Subtask Model
 *
 * @package  model
 * @author   Frederic Guillot
 */
class Subtask extends Base
{
    /**
     * SQL table name
     *
     * @var string
     */
    const TABLE = 'subtasks';

    /**
     * Task "done" status
     *
     * @var integer
     */
    const STATUS_DONE = 2;

    /**
     * Task "in progress" status
     *
     * @var integer
     */
    const STATUS_INPROGRESS = 1;

    /**
     * Task "todo" status
     *
     * @var integer
     */
    const STATUS_TODO = 0;

    /**
     * Events
     *
     * @var string
     */
    const EVENT_UPDATE = 'subtask.update';
    const EVENT_CREATE = 'subtask.create';
    const EVENT_DELETE = 'subtask.delete';

    /**
     * Get available status
     *
     * @access public
     * @return string[]
     */
    public function getStatusList()
    {
        return array(
            self::STATUS_TODO => t('Todo'),
            self::STATUS_INPROGRESS => t('In progress'),
            self::STATUS_DONE => t('Done'),
        );
    }

    /**
     * Add subtask status status to the resultset
     *
     * @access public
     * @param  array    $subtasks   Subtasks
     * @return array
     */
    public function addStatusName(array $subtasks)
    {
        $status = $this->getStatusList();

        foreach ($subtasks as &$subtask) {
            $subtask['status_name'] = $status[$subtask['status']];
            $subtask['timer_start_date'] = isset($subtask['timer_start_date']) ? $subtask['timer_start_date'] : 0;
            $subtask['is_timer_started'] = ! empty($subtask['timer_start_date']);
        }

        return $subtasks;
    }

    /**
     * Get the query to fetch subtasks assigned to a user
     *
     * @access public
     * @param  integer    $user_id         User id
     * @param  array      $status          List of status
     * @return \PicoDb\Table
     */
    public function getUserQuery($user_id, array $status)
    {
        return $this->db->table(Subtask::TABLE)
            ->columns(
                Subtask::TABLE.'.*',
                Task::TABLE.'.project_id',
                Task::TABLE.'.color_id',
                Task::TABLE.'.title AS task_name',
                Project::TABLE.'.name AS project_name'
            )
            ->subquery($this->subtaskTimeTracking->getTimerQuery($user_id), 'timer_start_date')
            ->eq('user_id', $user_id)
            ->eq(Project::TABLE.'.is_active', Project::ACTIVE)
            ->in(Subtask::TABLE.'.status', $status)
            ->join(Task::TABLE, 'id', 'task_id')
            ->join(Project::TABLE, 'id', 'project_id', Task::TABLE)
            ->callback(array($this, 'addStatusName'));
    }

    /**
     * Get all subtasks for a given task
     *
     * @access public
     * @param  integer   $task_id    Task id
     * @return array
     */
    public function getAll($task_id)
    {
        return $this->db
                    ->table(self::TABLE)
                    ->eq('task_id', $task_id)
                    ->columns(
                        self::TABLE.'.*',
                        User::TABLE.'.username',
                        User::TABLE.'.name'
                    )
                    ->subquery($this->subtaskTimeTracking->getTimerQuery($this->userSession->getId()), 'timer_start_date')
                    ->join(User::TABLE, 'id', 'user_id')
                    ->asc(self::TABLE.'.position')
                    ->callback(array($this, 'addStatusName'))
                    ->findAll();
    }

    /**
     * Get a subtask by the id
     *
     * @access public
     * @param  integer   $subtask_id    Subtask id
     * @param  bool      $more          Fetch more data
     * @return array
     */
    public function getById($subtask_id, $more = false)
    {
        if ($more) {
            return $this->db
                        ->table(self::TABLE)
                        ->eq(self::TABLE.'.id', $subtask_id)
                        ->columns(self::TABLE.'.*', User::TABLE.'.username', User::TABLE.'.name')
                        ->subquery($this->subtaskTimeTracking->getTimerQuery($this->userSession->getId()), 'timer_start_date')
                        ->join(User::TABLE, 'id', 'user_id')
                        ->callback(array($this, 'addStatusName'))
                        ->findOne();
        }

        return $this->db->table(self::TABLE)->eq('id', $subtask_id)->findOne();
    }

    /**
     * Prepare data before insert/update
     *
     * @access public
     * @param  array    $values    Form values
     */
    public function prepare(array &$values)
    {
        $this->removeFields($values, array('another_subtask'));
        $this->resetFields($values, array('time_estimated', 'time_spent'));
    }

    /**
     * Prepare data before insert
     *
     * @access public
     * @param  array    $values    Form values
     */
    public function prepareCreation(array &$values)
    {
        $this->prepare($values);

        $values['position'] = $this->getLastPosition($values['task_id']) + 1;
        $values['status'] = isset($values['status']) ? $values['status'] : self::STATUS_TODO;
        $values['time_estimated'] = isset($values['time_estimated']) ? $values['time_estimated'] : 0;
        $values['time_spent'] = isset($values['time_spent']) ? $values['time_spent'] : 0;
        $values['user_id'] = isset($values['user_id']) ? $values['user_id'] : 0;
    }

    /**
     * Get the position of the last column for a given project
     *
     * @access public
     * @param  integer  $task_id   Task id
     * @return integer
     */
    public function getLastPosition($task_id)
    {
        return (int) $this->db
                        ->table(self::TABLE)
                        ->eq('task_id', $task_id)
                        ->desc('position')
                        ->findOneColumn('position');
    }

    /**
     * Create a new subtask
     *
     * @access public
     * @param  array    $values    Form values
     * @return bool|integer
     */
    public function create(array $values)
    {
        $this->prepareCreation($values);
        $subtask_id = $this->persist(self::TABLE, $values);

        if ($subtask_id) {
            $this->container['dispatcher']->dispatch(
                self::EVENT_CREATE,
                new SubtaskEvent(array('id' => $subtask_id) + $values)
            );
        }

        return $subtask_id;
    }

    /**
     * Update
     *
     * @access public
     * @param  array $values      Form values
     * @param  bool  $fire_events If true, will be called an event
     * @return bool
     */
    public function update(array $values, $fire_events = true)
    {
        $this->prepare($values);
        $subtask = $this->getById($values['id']);
        $result = $this->db->table(self::TABLE)->eq('id', $values['id'])->save($values);

        if ($result && $fire_events) {
            $event = $subtask;
            $event['changes'] = array_diff_assoc($values, $subtask);
            $this->container['dispatcher']->dispatch(self::EVENT_UPDATE, new SubtaskEvent($event));
        }

        return $result;
    }

    /**
     * Close all subtasks of a task
     *
     * @access public
     * @param  integer  $task_id
     * @return boolean
     */
    public function closeAll($task_id)
    {
        return $this->db->table(self::TABLE)->eq('task_id', $task_id)->update(array('status' => self::STATUS_DONE));
    }

    /**
     * Get subtasks with consecutive positions
     *
     * If you remove a subtask, the positions are not anymore consecutives
     *
     * @access public
     * @param  integer  $task_id
     * @return array
     */
    public function getNormalizedPositions($task_id)
    {
        $subtasks = $this->db->hashtable(self::TABLE)->eq('task_id', $task_id)->asc('position')->getAll('id', 'position');
        $position = 1;

        foreach ($subtasks as $subtask_id => $subtask_position) {
            $subtasks[$subtask_id] = $position++;
        }

        return $subtasks;
    }

    /**
     * Save the new positions for a set of subtasks
     *
     * @access public
     * @param  array   $subtasks    Hashmap of column_id/column_position
     * @return boolean
     */
    public function savePositions(array $subtasks)
    {
        return $this->db->transaction(function (Database $db) use ($subtasks) {

            foreach ($subtasks as $subtask_id => $position) {
                if (! $db->table(Subtask::TABLE)->eq('id', $subtask_id)->update(array('position' => $position))) {
                    return false;
                }
            }
        });
    }

    /**
     * Move a subtask down, increment the position value
     *
     * @access public
     * @param  integer  $task_id
     * @param  integer  $subtask_id
     * @return boolean
     */
    public function moveDown($task_id, $subtask_id)
    {
        $subtasks = $this->getNormalizedPositions($task_id);
        $positions = array_flip($subtasks);

        if (isset($subtasks[$subtask_id]) && $subtasks[$subtask_id] < count($subtasks)) {
            $position = ++$subtasks[$subtask_id];
            $subtasks[$positions[$position]]--;

            return $this->savePositions($subtasks);
        }

        return false;
    }

    /**
     * Move a subtask up, decrement the position value
     *
     * @access public
     * @param  integer  $task_id
     * @param  integer  $subtask_id
     * @return boolean
     */
    public function moveUp($task_id, $subtask_id)
    {
        $subtasks = $this->getNormalizedPositions($task_id);
        $positions = array_flip($subtasks);

        if (isset($subtasks[$subtask_id]) && $subtasks[$subtask_id] > 1) {
            $position = --$subtasks[$subtask_id];
            $subtasks[$positions[$position]]++;

            return $this->savePositions($subtasks);
        }

        return false;
    }

    /**
     * Change the status of subtask
     *
     * @access public
     * @param  integer  $subtask_id
     * @return bool
     */
    public function toggleStatus($subtask_id)
    {
        $subtask = $this->getById($subtask_id);

        $values = array(
            'id' => $subtask['id'],
            'status' => ($subtask['status'] + 1) % 3,
            'task_id' => $subtask['task_id'],
        );

        if (empty($subtask['user_id']) && $this->userSession->isLogged()) {
            $values['user_id'] = $this->userSession->getId();
        }

        return $this->update($values);
    }

    /**
     * Get the subtask in progress for this user
     *
     * @access public
     * @param  integer   $user_id
     * @return array
     */
    public function getSubtaskInProgress($user_id)
    {
        return $this->db->table(self::TABLE)
                        ->eq('status', self::STATUS_INPROGRESS)
                        ->eq('user_id', $user_id)
                        ->findOne();
    }

    /**
     * Return true if the user have a subtask in progress
     *
     * @access public
     * @param  integer   $user_id
     * @return boolean
     */
    public function hasSubtaskInProgress($user_id)
    {
        return $this->config->get('subtask_restriction') == 1 &&
               $this->db->table(self::TABLE)
                        ->eq('status', self::STATUS_INPROGRESS)
                        ->eq('user_id', $user_id)
                        ->exists();
    }

    /**
     * Remove
     *
     * @access public
     * @param  integer   $subtask_id    Subtask id
     * @return bool
     */
    public function remove($subtask_id)
    {
        $subtask = $this->getById($subtask_id);
        $result = $this->db->table(self::TABLE)->eq('id', $subtask_id)->remove();

        if ($result) {
            $this->container['dispatcher']->dispatch(self::EVENT_DELETE, new SubtaskEvent($subtask));
        }

        return $result;
    }

    /**
     * Duplicate all subtasks to another task
     *
     * @access public
     * @param  integer   $src_task_id    Source task id
     * @param  integer   $dst_task_id    Destination task id
     * @return bool
     */
    public function duplicate($src_task_id, $dst_task_id)
    {
        return $this->db->transaction(function (Database $db) use ($src_task_id, $dst_task_id) {

            $subtasks = $db->table(Subtask::TABLE)
                                 ->columns('title', 'time_estimated', 'position')
                                 ->eq('task_id', $src_task_id)
                                 ->asc('position')
                                 ->findAll();

            foreach ($subtasks as &$subtask) {
                $subtask['task_id'] = $dst_task_id;

                if (! $db->table(Subtask::TABLE)->save($subtask)) {
                    return false;
                }
            }
        });
    }
}
