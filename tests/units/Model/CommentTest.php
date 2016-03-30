<?php

require_once __DIR__.'/../Base.php';

use Kanboard\Model\TaskCreation;
use Kanboard\Model\Project;
use Kanboard\Model\Comment;

class CommentTest extends Base
{
    public function testCreate()
    {
        $c = new Comment($this->container);
        $tc = new TaskCreation($this->container);
        $p = new Project($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'test1')));
        $this->assertEquals(1, $tc->create(array('title' => 'test', 'project_id' => 1, 'column_id' => 3, 'owner_id' => 1)));
        $this->assertEquals(1, $c->create(array('task_id' => 1, 'comment' => 'bla bla', 'user_id' => 1)));
        $this->assertEquals(2, $c->create(array('task_id' => 1, 'comment' => 'bla bla')));

        $comment = $c->getById(1);
        $this->assertNotEmpty($comment);
        $this->assertEquals('bla bla', $comment['comment']);
        $this->assertEquals(1, $comment['task_id']);
        $this->assertEquals(1, $comment['user_id']);
        $this->assertEquals('admin', $comment['username']);
        $this->assertEquals(time(), $comment['date_creation'], '', 3);

        $comment = $c->getById(2);
        $this->assertNotEmpty($comment);
        $this->assertEquals('bla bla', $comment['comment']);
        $this->assertEquals(1, $comment['task_id']);
        $this->assertEquals(0, $comment['user_id']);
        $this->assertEquals('', $comment['username']);
        $this->assertEquals(time(), $comment['date_creation'], '', 3);
    }

    public function testGetAll()
    {
        $c = new Comment($this->container);
        $tc = new TaskCreation($this->container);
        $p = new Project($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'test1')));
        $this->assertEquals(1, $tc->create(array('title' => 'test', 'project_id' => 1, 'column_id' => 3, 'owner_id' => 1)));
        $this->assertNotFalse($c->create(array('task_id' => 1, 'comment' => 'c1', 'user_id' => 1)));
        $this->assertNotFalse($c->create(array('task_id' => 1, 'comment' => 'c2', 'user_id' => 1)));
        $this->assertNotFalse($c->create(array('task_id' => 1, 'comment' => 'c3', 'user_id' => 1)));

        $comments = $c->getAll(1);

        $this->assertNotEmpty($comments);
        $this->assertEquals(3, count($comments));
        $this->assertEquals(1, $comments[0]['id']);
        $this->assertEquals(2, $comments[1]['id']);
        $this->assertEquals(3, $comments[2]['id']);

        $this->assertEquals(3, $c->count(1));
    }

    public function testUpdate()
    {
        $c = new Comment($this->container);
        $tc = new TaskCreation($this->container);
        $p = new Project($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'test1')));
        $this->assertEquals(1, $tc->create(array('title' => 'test', 'project_id' => 1, 'column_id' => 3, 'owner_id' => 1)));
        $this->assertNotFalse($c->create(array('task_id' => 1, 'comment' => 'c1', 'user_id' => 1)));
        $this->assertTrue($c->update(array('id' => 1, 'comment' => 'bla')));

        $comment = $c->getById(1);
        $this->assertNotEmpty($comment);
        $this->assertEquals('bla', $comment['comment']);
    }

    public function validateRemove()
    {
        $c = new Comment($this->container);
        $tc = new TaskCreation($this->container);
        $p = new Project($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'test1')));
        $this->assertEquals(1, $tc->create(array('title' => 'test', 'project_id' => 1, 'column_id' => 3, 'owner_id' => 1)));
        $this->assertTrue($c->create(array('task_id' => 1, 'comment' => 'c1', 'user_id' => 1)));

        $this->assertTrue($c->remove(1));
        $this->assertFalse($c->remove(1));
        $this->assertFalse($c->remove(1111));
    }
}
