<?php

namespace Kanboard\Model;

use Kanboard\Core\Base;
use Kanboard\Event\GenericEvent;

/**
 * User Mention
 *
 * @package  Kanboard\Model
 * @author   Frederic Guillot
 */
class UserMentionModel extends Base
{
    /**
     * Get list of mentioned users
     *
     * @access public
     * @param  string $content
     * @return array
     */
    public function getMentionedUsers($content)
    {
        $users = array();

        if (preg_match_all('/@([^\s]+)/', $content, $matches)) {
            $users = $this->db->table(UserModel::TABLE)
                ->columns('id', 'username', 'name', 'email', 'language')
                ->eq('notifications_enabled', 1)
                ->neq('id', $this->userSession->getId())
                ->in('username', array_unique($matches[1]))
                ->findAll();
        }

        return $users;
    }

    /**
     * Fire events for user mentions
     *
     * @access public
     * @param  string       $content
     * @param  string       $eventName
     * @param  GenericEvent $event
     */
    public function fireEvents($content, $eventName, GenericEvent $event)
    {
        if (empty($event['project_id'])) {
            $event['project_id'] = $this->taskFinderModel->getProjectId($event['task_id']);
        }

        $users = $this->getMentionedUsers($content);

        foreach ($users as $user) {
            if ($this->projectPermissionModel->isMember($event['project_id'], $user['id'])) {
                $event['mention'] = $user;
                $this->dispatcher->dispatch($eventName, $event);
            }
        }
    }
}
