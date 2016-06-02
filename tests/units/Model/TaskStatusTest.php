<?php

require_once __DIR__.'/../Base.php';

use Kanboard\Model\SwimlaneModel;
use Kanboard\Model\SubtaskModel;
use Kanboard\Model\TaskModel;
use Kanboard\Model\TaskCreationModel;
use Kanboard\Model\TaskFinderModel;
use Kanboard\Model\TaskStatusModel;
use Kanboard\Model\ProjectModel;

class TaskStatusTest extends Base
{
    public function testCloseBySwimlaneAndColumn()
    {
        $tc = new TaskCreationModel($this->container);
        $tf = new TaskFinderModel($this->container);
        $ts = new TaskStatusModel($this->container);
        $p = new ProjectModel($this->container);
        $s = new SwimlaneModel($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'test')));
        $this->assertEquals(1, $s->create(array('name' => 'test', 'project_id' => 1)));
        $this->assertEquals(1, $tc->create(array('title' => 'test', 'project_id' => 1)));
        $this->assertEquals(2, $tc->create(array('title' => 'test', 'project_id' => 1)));
        $this->assertEquals(3, $tc->create(array('title' => 'test', 'project_id' => 1, 'column_id' => 2)));
        $this->assertEquals(4, $tc->create(array('title' => 'test', 'project_id' => 1, 'swimlane_id' => 1)));
        $this->assertEquals(5, $tc->create(array('title' => 'test', 'project_id' => 1, 'is_active' => 0, 'date_completed' => strtotime('2015-01-01'))));

        $taskBefore = $tf->getById(5);

        $this->assertEquals(2, $tf->countByColumnAndSwimlaneId(1, 1, 0));
        $this->assertEquals(1, $tf->countByColumnAndSwimlaneId(1, 1, 1));
        $this->assertEquals(1, $tf->countByColumnAndSwimlaneId(1, 2, 0));

        $ts->closeTasksBySwimlaneAndColumn(0, 1);
        $this->assertEquals(0, $tf->countByColumnAndSwimlaneId(1, 1, 0));
        $this->assertEquals(1, $tf->countByColumnAndSwimlaneId(1, 1, 1));
        $this->assertEquals(1, $tf->countByColumnAndSwimlaneId(1, 2, 0));

        $ts->closeTasksBySwimlaneAndColumn(1, 1);
        $this->assertEquals(0, $tf->countByColumnAndSwimlaneId(1, 1, 0));
        $this->assertEquals(0, $tf->countByColumnAndSwimlaneId(1, 1, 1));
        $this->assertEquals(1, $tf->countByColumnAndSwimlaneId(1, 2, 0));

        $ts->closeTasksBySwimlaneAndColumn(0, 2);
        $this->assertEquals(0, $tf->countByColumnAndSwimlaneId(1, 1, 0));
        $this->assertEquals(0, $tf->countByColumnAndSwimlaneId(1, 1, 1));
        $this->assertEquals(0, $tf->countByColumnAndSwimlaneId(1, 2, 0));

        $taskAfter = $tf->getById(5);
        $this->assertEquals(strtotime('2015-01-01'), $taskAfter['date_completed']);
        $this->assertEquals($taskBefore['date_modification'], $taskAfter['date_modification']);
    }

    public function testStatus()
    {
        $tc = new TaskCreationModel($this->container);
        $tf = new TaskFinderModel($this->container);
        $ts = new TaskStatusModel($this->container);
        $p = new ProjectModel($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'test')));
        $this->assertEquals(1, $tc->create(array('title' => 'test', 'project_id' => 1)));

        // The task must be open

        $this->assertTrue($ts->isOpen(1));

        $task = $tf->getById(1);
        $this->assertNotEmpty($task);
        $this->assertEquals(TaskModel::STATUS_OPEN, $task['is_active']);
        $this->assertEquals(0, $task['date_completed']);
        $this->assertEquals(time(), $task['date_modification'], '', 1);

        // We close the task

        $this->container['dispatcher']->addListener(TaskModel::EVENT_CLOSE, array($this, 'onTaskClose'));
        $this->container['dispatcher']->addListener(TaskModel::EVENT_OPEN, array($this, 'onTaskOpen'));

        $this->assertTrue($ts->close(1));
        $this->assertTrue($ts->isClosed(1));

        $task = $tf->getById(1);
        $this->assertNotEmpty($task);
        $this->assertEquals(TaskModel::STATUS_CLOSED, $task['is_active']);
        $this->assertEquals(time(), $task['date_completed'], 'Bad completion timestamp', 1);
        $this->assertEquals(time(), $task['date_modification'], 'Bad modification timestamp', 1);

        // We open the task again

        $this->assertTrue($ts->open(1));
        $this->assertTrue($ts->isOpen(1));

        $task = $tf->getById(1);
        $this->assertNotEmpty($task);
        $this->assertEquals(TaskModel::STATUS_OPEN, $task['is_active']);
        $this->assertEquals(0, $task['date_completed']);
        $this->assertEquals(time(), $task['date_modification'], '', 1);

        $called = $this->container['dispatcher']->getCalledListeners();
        $this->assertArrayHasKey('task.close.TaskStatusTest::onTaskClose', $called);
        $this->assertArrayHasKey('task.open.TaskStatusTest::onTaskOpen', $called);
    }

    public function onTaskOpen($event)
    {
        $this->assertInstanceOf('Kanboard\Event\TaskEvent', $event);
        $this->assertArrayHasKey('task_id', $event);
        $this->assertNotEmpty($event['task_id']);
    }

    public function onTaskClose($event)
    {
        $this->assertInstanceOf('Kanboard\Event\TaskEvent', $event);
        $this->assertArrayHasKey('task_id', $event);
        $this->assertNotEmpty($event['task_id']);
    }

    public function testThatAllSubtasksAreClosed()
    {
        $ts = new TaskStatusModel($this->container);
        $tc = new TaskCreationModel($this->container);
        $s = new SubtaskModel($this->container);
        $p = new ProjectModel($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'test1')));
        $this->assertEquals(1, $tc->create(array('title' => 'test 1', 'project_id' => 1)));

        $this->assertEquals(1, $s->create(array('title' => 'subtask #1', 'task_id' => 1)));
        $this->assertEquals(2, $s->create(array('title' => 'subtask #2', 'task_id' => 1)));

        $this->assertTrue($ts->close(1));

        $subtasks = $s->getAll(1);
        $this->assertNotEmpty($subtasks);

        foreach ($subtasks as $subtask) {
            $this->assertEquals(SubtaskModel::STATUS_DONE, $subtask['status']);
        }
    }
}
