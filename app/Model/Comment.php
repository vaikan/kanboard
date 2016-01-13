<?php

namespace Kanboard\Model;

use Kanboard\Event\CommentEvent;
use SimpleValidator\Validator;
use SimpleValidator\Validators;

/**
 * Comment model
 *
 * @package  model
 * @author   Frederic Guillot
 */
class Comment extends Base
{
    /**
     * SQL table name
     *
     * @var string
     */
    const TABLE = 'comments';

    /**
     * Events
     *
     * @var string
     */
    const EVENT_UPDATE       = 'comment.update';
    const EVENT_CREATE       = 'comment.create';
    const EVENT_USER_MENTION = 'comment.user.mention';

    /**
     * Get all comments for a given task
     *
     * @access public
     * @param  integer  $task_id  Task id
     * @param  string   $sorting  ASC/DESC
     * @return array
     */
    public function getAll($task_id, $sorting = 'ASC')
    {
        return $this->db
            ->table(self::TABLE)
            ->columns(
                self::TABLE.'.id',
                self::TABLE.'.date_creation',
                self::TABLE.'.task_id',
                self::TABLE.'.user_id',
                self::TABLE.'.comment',
                User::TABLE.'.username',
                User::TABLE.'.name',
                User::TABLE.'.email'
            )
            ->join(User::TABLE, 'id', 'user_id')
            ->orderBy(self::TABLE.'.date_creation', $sorting)
            ->eq(self::TABLE.'.task_id', $task_id)
            ->findAll();
    }

    /**
     * Get a comment
     *
     * @access public
     * @param  integer  $comment_id  Comment id
     * @return array
     */
    public function getById($comment_id)
    {
        return $this->db
            ->table(self::TABLE)
            ->columns(
                self::TABLE.'.id',
                self::TABLE.'.task_id',
                self::TABLE.'.user_id',
                self::TABLE.'.date_creation',
                self::TABLE.'.comment',
                self::TABLE.'.reference',
                User::TABLE.'.username',
                User::TABLE.'.name'
            )
            ->join(User::TABLE, 'id', 'user_id')
            ->eq(self::TABLE.'.id', $comment_id)
            ->findOne();
    }

    /**
     * Get the number of comments for a given task
     *
     * @access public
     * @param  integer  $task_id  Task id
     * @return integer
     */
    public function count($task_id)
    {
        return $this->db
            ->table(self::TABLE)
            ->eq(self::TABLE.'.task_id', $task_id)
            ->count();
    }

    /**
     * Create a new comment
     *
     * @access public
     * @param  array    $values   Form values
     * @return boolean|integer
     */
    public function create(array $values)
    {
        $values['date_creation'] = time();
        $comment_id = $this->persist(self::TABLE, $values);

        if ($comment_id) {
            $event = new CommentEvent(array('id' => $comment_id) + $values);
            $this->dispatcher->dispatch(self::EVENT_CREATE, $event);
            $this->userMention->fireEvents($values['comment'], self::EVENT_USER_MENTION, $event);
        }

        return $comment_id;
    }

    /**
     * Update a comment in the database
     *
     * @access public
     * @param  array    $values   Form values
     * @return boolean
     */
    public function update(array $values)
    {
        $result = $this->db
                    ->table(self::TABLE)
                    ->eq('id', $values['id'])
                    ->update(array('comment' => $values['comment']));

        if ($result) {
            $this->container['dispatcher']->dispatch(self::EVENT_UPDATE, new CommentEvent($values));
        }

        return $result;
    }

    /**
     * Remove a comment
     *
     * @access public
     * @param  integer  $comment_id  Comment id
     * @return boolean
     */
    public function remove($comment_id)
    {
        return $this->db->table(self::TABLE)->eq('id', $comment_id)->remove();
    }

    /**
     * Validate comment creation
     *
     * @access public
     * @param  array   $values           Required parameters to save an action
     * @return array   $valid, $errors   [0] = Success or not, [1] = List of errors
     */
    public function validateCreation(array $values)
    {
        $rules = array(
            new Validators\Required('task_id', t('This value is required')),
        );

        $v = new Validator($values, array_merge($rules, $this->commonValidationRules()));

        return array(
            $v->execute(),
            $v->getErrors()
        );
    }

    /**
     * Validate comment modification
     *
     * @access public
     * @param  array   $values           Required parameters to save an action
     * @return array   $valid, $errors   [0] = Success or not, [1] = List of errors
     */
    public function validateModification(array $values)
    {
        $rules = array(
            new Validators\Required('id', t('This value is required')),
        );

        $v = new Validator($values, array_merge($rules, $this->commonValidationRules()));

        return array(
            $v->execute(),
            $v->getErrors()
        );
    }

    /**
     * Common validation rules
     *
     * @access private
     * @return array
     */
    private function commonValidationRules()
    {
        return array(
            new Validators\Integer('id', t('This value must be an integer')),
            new Validators\Integer('task_id', t('This value must be an integer')),
            new Validators\Integer('user_id', t('This value must be an integer')),
            new Validators\MaxLength('reference', t('The maximum length is %d characters', 50), 50),
            new Validators\Required('comment', t('Comment is required'))
        );
    }
}
