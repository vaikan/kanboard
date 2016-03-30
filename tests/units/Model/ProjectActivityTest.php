<?php

require_once __DIR__.'/../Base.php';

use Kanboard\Model\Task;
use Kanboard\Model\TaskFinder;
use Kanboard\Model\TaskCreation;
use Kanboard\Model\ProjectActivity;
use Kanboard\Model\Project;

class ProjectActivityTest extends Base
{
    public function testDecode()
    {
        $e = new ProjectActivity($this->container);
        $input = array('test');
        $serialized = serialize($input);
        $json = json_encode($input);

        $this->assertEquals($input, $e->decode($serialized));
        $this->assertEquals($input, $e->decode($json));
    }

    public function testCreation()
    {
        $e = new ProjectActivity($this->container);
        $tc = new TaskCreation($this->container);
        $tf = new TaskFinder($this->container);
        $p = new Project($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'Project #1')));
        $this->assertEquals(1, $tc->create(array('title' => 'Task #1', 'project_id' => 1)));
        $this->assertEquals(2, $tc->create(array('title' => 'Task #2', 'project_id' => 1)));

        $this->assertTrue($e->createEvent(1, 1, 1, Task::EVENT_CLOSE, array('task' => $tf->getbyId(1))));
        $this->assertTrue($e->createEvent(1, 2, 1, Task::EVENT_UPDATE, array('task' => $tf->getById(2))));
        $this->assertFalse($e->createEvent(1, 1, 0, Task::EVENT_OPEN, array('task' => $tf->getbyId(1))));

        $events = $e->getProject(1);

        $this->assertNotEmpty($events);
        $this->assertTrue(is_array($events));
        $this->assertEquals(2, count($events));
        $this->assertEquals(time(), $events[0]['date_creation'], '', 1);
        $this->assertEquals(Task::EVENT_UPDATE, $events[0]['event_name']);
        $this->assertEquals(Task::EVENT_CLOSE, $events[1]['event_name']);
    }

    public function testFetchAllContent()
    {
        $e = new ProjectActivity($this->container);
        $tc = new TaskCreation($this->container);
        $tf = new TaskFinder($this->container);
        $p = new Project($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'Project #1')));
        $this->assertEquals(1, $tc->create(array('title' => 'Task #1', 'project_id' => 1)));

        $nb_events = 80;

        for ($i = 0; $i < $nb_events; $i++) {
            $this->assertTrue($e->createEvent(1, 1, 1, Task::EVENT_UPDATE, array('task' => $tf->getbyId(1))));
        }

        $events = $e->getProject(1);

        $this->assertNotEmpty($events);
        $this->assertTrue(is_array($events));
        $this->assertEquals(50, count($events));
        $this->assertEquals('admin', $events[0]['author']);
        $this->assertNotEmpty($events[0]['event_title']);
        $this->assertNotEmpty($events[0]['event_content']);
    }

    public function testCleanup()
    {
        $e = new ProjectActivity($this->container);
        $tc = new TaskCreation($this->container);
        $tf = new TaskFinder($this->container);
        $p = new Project($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'Project #1')));
        $this->assertEquals(1, $tc->create(array('title' => 'Task #1', 'project_id' => 1)));

        $max = 15;
        $nb_events = 100;
        $task = $tf->getbyId(1);

        for ($i = 0; $i < $nb_events; $i++) {
            $this->assertTrue($e->createEvent(1, 1, 1, Task::EVENT_CLOSE, array('task' => $task)));
        }

        $this->assertEquals($nb_events, $this->container['db']->table('project_activities')->count());
        $e->cleanup($max);

        $events = $e->getProject(1);

        $this->assertNotEmpty($events);
        $this->assertCount($max, $events);
        $this->assertEquals(100, $events[0]['id']);
        $this->assertEquals(99, $events[1]['id']);
        $this->assertEquals(86, $events[14]['id']);
    }
}
