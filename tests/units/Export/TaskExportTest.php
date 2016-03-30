<?php

require_once __DIR__ . '/../Base.php';

use Kanboard\Model\TaskCreation;
use Kanboard\Export\TaskExport;
use Kanboard\Model\Project;
use Kanboard\Model\Category;
use Kanboard\Model\Swimlane;

class TaskExportTest extends Base
{
    public function testExport()
    {
        $tc = new TaskCreation($this->container);
        $p = new Project($this->container);
        $c = new Category($this->container);
        $e = new TaskExport($this->container);
        $s = new Swimlane($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'Export Project')));

        $this->assertEquals(1, $s->create(array('project_id' => 1, 'name' => 'S1')));
        $this->assertEquals(2, $s->create(array('project_id' => 1, 'name' => 'S2')));

        $this->assertNotFalse($c->create(array('name' => 'Category #1', 'project_id' => 1)));
        $this->assertNotFalse($c->create(array('name' => 'Category #2', 'project_id' => 1)));
        $this->assertNotFalse($c->create(array('name' => 'Category #3', 'project_id' => 1)));

        for ($i = 1; $i <= 100; $i++) {
            $task = array(
                'title' => 'Task #'.$i,
                'project_id' => 1,
                'column_id' => rand(1, 3),
                'creator_id' => rand(0, 1),
                'owner_id' => rand(0, 1),
                'color_id' => rand(0, 1) === 0 ? 'green' : 'purple',
                'category_id' => rand(0, 3),
                'date_due' => array_rand(array(0, date('Y-m-d'), date('Y-m-d', strtotime('+'.$i.'day')))),
                'score' => rand(0, 21),
                'swimlane_id' => rand(0, 2),
            );

            $this->assertEquals($i, $tc->create($task));
        }

        $rows = $e->export(1, strtotime('-1 day'), strtotime('+1 day'));

        $this->assertEquals($i, count($rows));
        $this->assertEquals('Task Id', $rows[0][0]);
        $this->assertEquals(1, $rows[1][0]);
        $this->assertEquals('Task #'.($i - 1), $rows[$i - 1][13]);
        $this->assertTrue(in_array($rows[$i - 1][4], array('Default swimlane', 'S1', 'S2')));
    }
}
