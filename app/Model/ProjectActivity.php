<?php

namespace Kanboard\Model;

/**
 * Project activity model
 *
 * @package  model
 * @author   Frederic Guillot
 */
class ProjectActivity extends Base
{
    /**
     * SQL table name
     *
     * @var string
     */
    const TABLE = 'project_activities';

    /**
     * Maximum number of events
     *
     * @var integer
     */
    const MAX_EVENTS = 1000;

    /**
     * Add a new event for the project
     *
     * @access public
     * @param  integer     $project_id      Project id
     * @param  integer     $task_id         Task id
     * @param  integer     $creator_id      User id
     * @param  string      $event_name      Event name
     * @param  array       $data            Event data (will be serialized)
     * @return boolean
     */
    public function createEvent($project_id, $task_id, $creator_id, $event_name, array $data)
    {
        $values = array(
            'project_id' => $project_id,
            'task_id' => $task_id,
            'creator_id' => $creator_id,
            'event_name' => $event_name,
            'date_creation' => time(),
            'data' => json_encode($data),
        );

        $this->cleanup(self::MAX_EVENTS - 1);
        return $this->db->table(self::TABLE)->insert($values);
    }

    /**
     * Get all events for the given project
     *
     * @access public
     * @param  integer     $project_id      Project id
     * @param  integer     $limit           Maximum events number
     * @param  integer     $start           Timestamp of earliest activity
     * @param  integer     $end             Timestamp of latest activity
     * @return array
     */
    public function getProject($project_id, $limit = 50, $start = null, $end = null)
    {
        return $this->getProjects(array($project_id), $limit, $start, $end);
    }

    /**
     * Get all events for the given projects list
     *
     * @access public
     * @param  integer[]   $project_ids     Projects id
     * @param  integer     $limit           Maximum events number
     * @param  integer     $start           Timestamp of earliest activity
     * @param  integer     $end             Timestamp of latest activity
     * @return array
     */
    public function getProjects(array $project_ids, $limit = 50, $start = null, $end = null)
    {
        if (empty($project_ids)) {
            return array();
        }

        $query = $this
                    ->db
                    ->table(self::TABLE)
                    ->columns(
                        self::TABLE.'.*',
                        User::TABLE.'.username AS author_username',
                        User::TABLE.'.name AS author_name',
                        User::TABLE.'.email',
                        User::TABLE.'.avatar_path'
                    )
                    ->in('project_id', $project_ids)
                    ->join(User::TABLE, 'id', 'creator_id')
                    ->desc(self::TABLE.'.id')
                    ->limit($limit);

        return $this->getEvents($query, $start, $end);
    }

    /**
     * Get all events for the given task
     *
     * @access public
     * @param  integer     $task_id         Task id
     * @param  integer     $limit           Maximum events number
     * @param  integer     $start           Timestamp of earliest activity
     * @param  integer     $end             Timestamp of latest activity
     * @return array
     */
    public function getTask($task_id, $limit = 50, $start = null, $end = null)
    {
        $query = $this
                    ->db
                    ->table(self::TABLE)
                    ->columns(
                        self::TABLE.'.*',
                        User::TABLE.'.username AS author_username',
                        User::TABLE.'.name AS author_name',
                        User::TABLE.'.email',
                        User::TABLE.'.avatar_path'
                    )
                    ->eq('task_id', $task_id)
                    ->join(User::TABLE, 'id', 'creator_id')
                    ->desc(self::TABLE.'.id')
                    ->limit($limit);

        return $this->getEvents($query, $start, $end);
    }

    /**
     * Common function to return events
     *
     * @access public
     * @param  \PicoDb\Table   $query           PicoDb Query
     * @param  integer         $start           Timestamp of earliest activity
     * @param  integer         $end             Timestamp of latest activity
     * @return array
     */
    private function getEvents(\PicoDb\Table $query, $start, $end)
    {
        if (! is_null($start)) {
            $query->gte('date_creation', $start);
        }

        if (! is_null($end)) {
            $query->lte('date_creation', $end);
        }

        $events = $query->findAll();

        foreach ($events as &$event) {
            $event += $this->decode($event['data']);
            unset($event['data']);

            $event['author'] = $event['author_name'] ?: $event['author_username'];
            $event['event_title'] = $this->notification->getTitleWithAuthor($event['author'], $event['event_name'], $event);
            $event['event_content'] = $this->getContent($event);
        }

        return $events;
    }

    /**
     * Remove old event entries to avoid large table
     *
     * @access public
     * @param  integer    $max    Maximum number of items to keep in the table
     */
    public function cleanup($max)
    {
        $total = $this->db->table(self::TABLE)->count();

        if ($total > $max) {
            $ids = $this->db->table(self::TABLE)->asc('id')->limit($total - $max)->findAllByColumn('id');
            $this->db->table(self::TABLE)->in('id', $ids)->remove();
        }
    }

    /**
     * Get the event html content
     *
     * @access public
     * @param  array     $params    Event properties
     * @return string
     */
    public function getContent(array $params)
    {
        return $this->template->render(
            'event/'.str_replace('.', '_', $params['event_name']),
            $params
        );
    }

    /**
     * Decode event data, supports unserialize() and json_decode()
     *
     * @access public
     * @param  string   $data   Serialized data
     * @return array
     */
    public function decode($data)
    {
        if ($data{0} === 'a') {
            return unserialize($data);
        }

        return json_decode($data, true) ?: array();
    }
}
