<?php

namespace Kanboard\Model;

use Kanboard\Core\Base;

/**
 * Task model
 *
 * @package  Kanboard\Model
 * @author   Frederic Guillot
 */
class TaskModel extends Base
{
    /**
     * SQL table name
     *
     * @var string
     */
    const TABLE               = 'tasks';

    /**
     * Task status
     *
     * @var integer
     */
    const STATUS_OPEN         = 1;
    const STATUS_CLOSED       = 0;

    /**
     * Events
     *
     * @var string
     */
    const EVENT_MOVE_PROJECT    = 'task.move.project';
    const EVENT_MOVE_COLUMN     = 'task.move.column';
    const EVENT_MOVE_POSITION   = 'task.move.position';
    const EVENT_MOVE_SWIMLANE   = 'task.move.swimlane';
    const EVENT_UPDATE          = 'task.update';
    const EVENT_CREATE          = 'task.create';
    const EVENT_CLOSE           = 'task.close';
    const EVENT_OPEN            = 'task.open';
    const EVENT_CREATE_UPDATE   = 'task.create_update';
    const EVENT_ASSIGNEE_CHANGE = 'task.assignee_change';
    const EVENT_OVERDUE         = 'task.overdue';
    const EVENT_USER_MENTION    = 'task.user.mention';
    const EVENT_DAILY_CRONJOB   = 'task.cronjob.daily';

    /**
     * Recurrence: status
     *
     * @var integer
     */
    const RECURRING_STATUS_NONE        = 0;
    const RECURRING_STATUS_PENDING     = 1;
    const RECURRING_STATUS_PROCESSED   = 2;

    /**
     * Recurrence: trigger
     *
     * @var integer
     */
    const RECURRING_TRIGGER_FIRST_COLUMN  = 0;
    const RECURRING_TRIGGER_LAST_COLUMN   = 1;
    const RECURRING_TRIGGER_CLOSE         = 2;

    /**
     * Recurrence: timeframe
     *
     * @var integer
     */
    const RECURRING_TIMEFRAME_DAYS    = 0;
    const RECURRING_TIMEFRAME_MONTHS  = 1;
    const RECURRING_TIMEFRAME_YEARS   = 2;

    /**
     * Recurrence: base date used to calculate new due date
     *
     * @var integer
     */
    const RECURRING_BASEDATE_DUEDATE      = 0;
    const RECURRING_BASEDATE_TRIGGERDATE  = 1;

    /**
     * Remove a task
     *
     * @access public
     * @param  integer   $task_id   Task id
     * @return boolean
     */
    public function remove($task_id)
    {
        if (! $this->taskFinderModel->exists($task_id)) {
            return false;
        }

        $this->taskFileModel->removeAll($task_id);

        return $this->db->table(self::TABLE)->eq('id', $task_id)->remove();
    }

    /**
     * Get a the task id from a text
     *
     * Example: "Fix bug #1234" will return 1234
     *
     * @access public
     * @param  string   $message   Text
     * @return integer
     */
    public function getTaskIdFromText($message)
    {
        if (preg_match('!#(\d+)!i', $message, $matches) && isset($matches[1])) {
            return $matches[1];
        }

        return 0;
    }

    /**
     * Return the list user selectable recurrence status
     *
     * @access public
     * @return array
     */
    public function getRecurrenceStatusList()
    {
        return array(
            TaskModel::RECURRING_STATUS_NONE    => t('No'),
            TaskModel::RECURRING_STATUS_PENDING => t('Yes'),
        );
    }

    /**
     * Return the list recurrence triggers
     *
     * @access public
     * @return array
     */
    public function getRecurrenceTriggerList()
    {
        return array(
            TaskModel::RECURRING_TRIGGER_FIRST_COLUMN => t('When task is moved from first column'),
            TaskModel::RECURRING_TRIGGER_LAST_COLUMN  => t('When task is moved to last column'),
            TaskModel::RECURRING_TRIGGER_CLOSE        => t('When task is closed'),
        );
    }

    /**
     * Return the list options to calculate recurrence due date
     *
     * @access public
     * @return array
     */
    public function getRecurrenceBasedateList()
    {
        return array(
            TaskModel::RECURRING_BASEDATE_DUEDATE     => t('Existing due date'),
            TaskModel::RECURRING_BASEDATE_TRIGGERDATE => t('Action date'),
        );
    }

    /**
     * Return the list recurrence timeframes
     *
     * @access public
     * @return array
     */
    public function getRecurrenceTimeframeList()
    {
        return array(
            TaskModel::RECURRING_TIMEFRAME_DAYS   => t('Day(s)'),
            TaskModel::RECURRING_TIMEFRAME_MONTHS => t('Month(s)'),
            TaskModel::RECURRING_TIMEFRAME_YEARS  => t('Year(s)'),
        );
    }

    /**
     * Get task progress based on the column position
     *
     * @access public
     * @param  array    $task
     * @param  array    $columns
     * @return integer
     */
    public function getProgress(array $task, array $columns)
    {
        if ($task['is_active'] == self::STATUS_CLOSED) {
            return 100;
        }

        $position = 0;

        foreach ($columns as $column_id => $column_title) {
            if ($column_id == $task['column_id']) {
                break;
            }

            $position++;
        }

        return round(($position * 100) / count($columns), 1);
    }

    /**
     * Helper method to duplicate all tasks to another project
     *
     * @access public
     * @param  integer $src_project_id
     * @param  integer $dst_project_id
     * @return boolean
     */
    public function duplicate($src_project_id, $dst_project_id)
    {
        $task_ids = $this->taskFinderModel->getAllIds($src_project_id, array(TaskModel::STATUS_OPEN, TaskModel::STATUS_CLOSED));

        foreach ($task_ids as $task_id) {
            if (! $this->taskDuplicationModel->duplicateToProject($task_id, $dst_project_id)) {
                return false;
            }
        }

        return true;
    }
}
