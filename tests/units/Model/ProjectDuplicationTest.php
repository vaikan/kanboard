<?php

require_once __DIR__.'/../Base.php';

use Kanboard\Model\Action;
use Kanboard\Model\Project;
use Kanboard\Model\Category;
use Kanboard\Model\ProjectUserRole;
use Kanboard\Model\ProjectDuplication;
use Kanboard\Model\User;
use Kanboard\Model\Swimlane;
use Kanboard\Model\Task;
use Kanboard\Model\TaskCreation;
use Kanboard\Model\TaskFinder;
use Kanboard\Core\Security\Role;

class ProjectDuplicationTest extends Base
{
    public function testProjectName()
    {
        $pd = new ProjectDuplication($this->container);

        $this->assertEquals('test (Clone)', $pd->getClonedProjectName('test'));

        $this->assertEquals(50, strlen($pd->getClonedProjectName(str_repeat('a', 50))));
        $this->assertEquals(str_repeat('a', 42).' (Clone)', $pd->getClonedProjectName(str_repeat('a', 50)));

        $this->assertEquals(50, strlen($pd->getClonedProjectName(str_repeat('a', 60))));
        $this->assertEquals(str_repeat('a', 42).' (Clone)', $pd->getClonedProjectName(str_repeat('a', 60)));
    }

    public function testCopyProjectWithLongName()
    {
        $p = new Project($this->container);
        $pd = new ProjectDuplication($this->container);

        $this->assertEquals(1, $p->create(array('name' => str_repeat('a', 50))));
        $this->assertEquals(2, $pd->duplicate(1));

        $project = $p->getById(2);
        $this->assertNotEmpty($project);
        $this->assertEquals(str_repeat('a', 42).' (Clone)', $project['name']);
    }

    public function testClonePublicProject()
    {
        $p = new Project($this->container);
        $pd = new ProjectDuplication($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'Public')));
        $this->assertEquals(2, $pd->duplicate(1));

        $project = $p->getById(2);
        $this->assertNotEmpty($project);
        $this->assertEquals('Public (Clone)', $project['name']);
        $this->assertEquals(0, $project['is_private']);
        $this->assertEquals(0, $project['is_public']);
        $this->assertEmpty($project['token']);
    }

    public function testClonePrivateProject()
    {
        $p = new Project($this->container);
        $pd = new ProjectDuplication($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'Private', 'is_private' => 1), 1, true));
        $this->assertEquals(2, $pd->duplicate(1));

        $project = $p->getById(2);
        $this->assertNotEmpty($project);
        $this->assertEquals('Private (Clone)', $project['name']);
        $this->assertEquals(1, $project['is_private']);
        $this->assertEquals(0, $project['is_public']);
        $this->assertEmpty($project['token']);

        $pp = new ProjectUserRole($this->container);

        $this->assertEquals(array(1 => 'admin'), $pp->getAssignableUsers(1));
        $this->assertEquals(array(1 => 'admin'), $pp->getAssignableUsers(2));
    }

    public function testCloneProjectWithCategories()
    {
        $p = new Project($this->container);
        $c = new Category($this->container);
        $pd = new ProjectDuplication($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'P1')));

        $this->assertEquals(1, $c->create(array('name' => 'C1', 'project_id' => 1)));
        $this->assertEquals(2, $c->create(array('name' => 'C2', 'project_id' => 1)));
        $this->assertEquals(3, $c->create(array('name' => 'C3', 'project_id' => 1)));

        $this->assertEquals(2, $pd->duplicate(1));

        $project = $p->getById(2);
        $this->assertNotEmpty($project);
        $this->assertEquals('P1 (Clone)', $project['name']);

        $categories = $c->getAll(2);
        $this->assertNotempty($categories);
        $this->assertEquals(3, count($categories));

        $this->assertEquals(4, $categories[0]['id']);
        $this->assertEquals('C1', $categories[0]['name']);

        $this->assertEquals(5, $categories[1]['id']);
        $this->assertEquals('C2', $categories[1]['name']);

        $this->assertEquals(6, $categories[2]['id']);
        $this->assertEquals('C3', $categories[2]['name']);
    }

    public function testCloneProjectWithUsers()
    {
        $p = new Project($this->container);
        $c = new Category($this->container);
        $pp = new ProjectUserRole($this->container);
        $u = new User($this->container);
        $pd = new ProjectDuplication($this->container);

        $this->assertEquals(2, $u->create(array('username' => 'unittest1', 'password' => 'unittest')));
        $this->assertEquals(3, $u->create(array('username' => 'unittest2', 'password' => 'unittest')));
        $this->assertEquals(4, $u->create(array('username' => 'unittest3', 'password' => 'unittest')));

        $this->assertEquals(1, $p->create(array('name' => 'P1')));
        $this->assertTrue($pp->addUser(1, 2, Role::PROJECT_MEMBER));
        $this->assertTrue($pp->addUser(1, 3, Role::PROJECT_MEMBER));
        $this->assertTrue($pp->addUser(1, 4, Role::PROJECT_MANAGER));
        $this->assertEquals(Role::PROJECT_MEMBER, $pp->getUserRole(1, 2));
        $this->assertEquals(Role::PROJECT_MEMBER, $pp->getUserRole(1, 3));
        $this->assertEquals(Role::PROJECT_MANAGER, $pp->getUserRole(1, 4));

        $this->assertEquals(2, $pd->duplicate(1));

        $project = $p->getById(2);
        $this->assertNotEmpty($project);
        $this->assertEquals('P1 (Clone)', $project['name']);

        $this->assertEquals(3, count($pp->getUsers(2)));
        $this->assertEquals(Role::PROJECT_MEMBER, $pp->getUserRole(2, 2));
        $this->assertEquals(Role::PROJECT_MEMBER, $pp->getUserRole(2, 3));
        $this->assertEquals(Role::PROJECT_MANAGER, $pp->getUserRole(2, 4));
    }

    public function testCloneProjectWithActionTaskAssignCurrentUser()
    {
        $p = new Project($this->container);
        $a = new Action($this->container);
        $pd = new ProjectDuplication($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'P1')));

        $this->assertEquals(1, $a->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_MOVE_COLUMN,
            'action_name' => 'TaskAssignCurrentUser',
            'params' => array('column_id' => 2),
        )));

        $this->assertEquals(2, $pd->duplicate(1));

        $actions = $a->getAllByProject(2);

        $this->assertNotEmpty($actions);
        $this->assertEquals('TaskAssignCurrentUser', $actions[0]['action_name']);
        $this->assertNotEmpty($actions[0]['params']);
        $this->assertEquals(6, $actions[0]['params']['column_id']);
    }

    public function testCloneProjectWithActionTaskAssignColorCategory()
    {
        $p = new Project($this->container);
        $a = new Action($this->container);
        $c = new Category($this->container);
        $pd = new ProjectDuplication($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'P1')));

        $this->assertEquals(1, $c->create(array('name' => 'C1', 'project_id' => 1)));
        $this->assertEquals(2, $c->create(array('name' => 'C2', 'project_id' => 1)));
        $this->assertEquals(3, $c->create(array('name' => 'C3', 'project_id' => 1)));

        $this->assertEquals(1, $a->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_CREATE_UPDATE,
            'action_name' => 'TaskAssignColorCategory',
            'params' => array('color_id' => 'blue', 'category_id' => 2),
        )));

        $this->assertEquals(2, $pd->duplicate(1));

        $actions = $a->getAllByProject(2);

        $this->assertNotEmpty($actions);
        $this->assertEquals('TaskAssignColorCategory', $actions[0]['action_name']);
        $this->assertNotEmpty($actions[0]['params']);
        $this->assertEquals('blue', $actions[0]['params']['color_id']);
        $this->assertEquals(5, $actions[0]['params']['category_id']);
    }

    public function testCloneProjectWithSwimlanesAndTasks()
    {
        $p = new Project($this->container);
        $pd = new ProjectDuplication($this->container);
        $s = new Swimlane($this->container);
        $tc = new TaskCreation($this->container);
        $tf = new TaskFinder($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'P1')));

        // create initial swimlanes
        $this->assertEquals(1, $s->create(array('project_id' => 1, 'name' => 'S1')));
        $this->assertEquals(2, $s->create(array('project_id' => 1, 'name' => 'S2')));
        $this->assertEquals(3, $s->create(array('project_id' => 1, 'name' => 'S3')));

        $default_swimlane1 = $s->getDefault(1);
        $default_swimlane1['default_swimlane'] = 'New Default';

        $this->assertTrue($s->updateDefault($default_swimlane1));

        //create initial tasks
        $this->assertEquals(1, $tc->create(array('title' => 'T1', 'project_id' => 1, 'column_id' => 1, 'owner_id' => 1)));
        $this->assertEquals(2, $tc->create(array('title' => 'T2', 'project_id' => 1, 'column_id' => 2, 'owner_id' => 1)));
        $this->assertEquals(3, $tc->create(array('title' => 'T3', 'project_id' => 1, 'column_id' => 3, 'owner_id' => 1)));

        $this->assertNotFalse($pd->duplicate(1, array('category', 'action', 'swimlane', 'task')));
        $project = $p->getByName('P1 (Clone)');
        $this->assertNotFalse($project);
        $project_id = $project['id'];

        // Check if Swimlanes have been duplicated
        $swimlanes = $s->getAll($project_id);

        $this->assertCount(3, $swimlanes);
        $this->assertEquals(4, $swimlanes[0]['id']);
        $this->assertEquals('S1', $swimlanes[0]['name']);
        $this->assertEquals(5, $swimlanes[1]['id']);
        $this->assertEquals('S2', $swimlanes[1]['name']);
        $this->assertEquals(6, $swimlanes[2]['id']);
        $this->assertEquals('S3', $swimlanes[2]['name']);
        $new_default = $s->getDefault($project_id);
        $this->assertEquals('New Default', $new_default['default_swimlane']);

        // Check if Tasks have been duplicated

        $tasks = $tf->getAll($project_id);

        $this->assertCount(3, $tasks);
        // $this->assertEquals(4, $tasks[0]['id']);
        $this->assertEquals('T1', $tasks[0]['title']);
        // $this->assertEquals(5, $tasks[1]['id']);
        $this->assertEquals('T2', $tasks[1]['title']);
        // $this->assertEquals(6, $tasks[2]['id']);
        $this->assertEquals('T3', $tasks[2]['title']);

        $p->remove($project_id);

        $this->assertFalse($p->exists($project_id));
        $this->assertCount(0, $s->getAll($project_id));
        $this->assertCount(0, $tf->getAll($project_id));
    }

    public function testCloneProjectWithSwimlanes()
    {
        $p = new Project($this->container);
        $pd = new ProjectDuplication($this->container);
        $s = new Swimlane($this->container);
        $tc = new TaskCreation($this->container);
        $tf = new TaskFinder($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'P1')));

        // create initial swimlanes
        $this->assertEquals(1, $s->create(array('project_id' => 1, 'name' => 'S1')));
        $this->assertEquals(2, $s->create(array('project_id' => 1, 'name' => 'S2')));
        $this->assertEquals(3, $s->create(array('project_id' => 1, 'name' => 'S3')));

        $default_swimlane1 = $s->getDefault(1);
        $default_swimlane1['default_swimlane'] = 'New Default';

        $this->assertTrue($s->updateDefault($default_swimlane1));

        //create initial tasks
        $this->assertEquals(1, $tc->create(array('title' => 'T1', 'project_id' => 1, 'column_id' => 1, 'owner_id' => 1)));
        $this->assertEquals(2, $tc->create(array('title' => 'T2', 'project_id' => 1, 'column_id' => 2, 'owner_id' => 1)));
        $this->assertEquals(3, $tc->create(array('title' => 'T3', 'project_id' => 1, 'column_id' => 3, 'owner_id' => 1)));

        $this->assertNotFalse($pd->duplicate(1, array('category', 'action', 'swimlane')));
        $project = $p->getByName('P1 (Clone)');
        $this->assertNotFalse($project);
        $project_id = $project['id'];

        $swimlanes = $s->getAll($project_id);

        $this->assertCount(3, $swimlanes);
        $this->assertEquals(4, $swimlanes[0]['id']);
        $this->assertEquals('S1', $swimlanes[0]['name']);
        $this->assertEquals(5, $swimlanes[1]['id']);
        $this->assertEquals('S2', $swimlanes[1]['name']);
        $this->assertEquals(6, $swimlanes[2]['id']);
        $this->assertEquals('S3', $swimlanes[2]['name']);
        $new_default = $s->getDefault($project_id);
        $this->assertEquals('New Default', $new_default['default_swimlane']);

        // Check if Tasks have NOT been duplicated
        $this->assertCount(0, $tf->getAll($project_id));
    }

    public function testCloneProjectWithTasks()
    {
        $p = new Project($this->container);
        $pd = new ProjectDuplication($this->container);
        $s = new Swimlane($this->container);
        $tc = new TaskCreation($this->container);
        $tf = new TaskFinder($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'P1')));

        // create initial swimlanes
        $this->assertEquals(1, $s->create(array('project_id' => 1, 'name' => 'S1')));
        $this->assertEquals(2, $s->create(array('project_id' => 1, 'name' => 'S2')));
        $this->assertEquals(3, $s->create(array('project_id' => 1, 'name' => 'S3')));

        $default_swimlane1 = $s->getDefault(1);
        $default_swimlane1['default_swimlane'] = 'New Default';

        $this->assertTrue($s->updateDefault($default_swimlane1));

        //create initial tasks
        $this->assertEquals(1, $tc->create(array('title' => 'T1', 'project_id' => 1, 'column_id' => 1, 'owner_id' => 1)));
        $this->assertEquals(2, $tc->create(array('title' => 'T2', 'project_id' => 1, 'column_id' => 2, 'owner_id' => 1)));
        $this->assertEquals(3, $tc->create(array('title' => 'T3', 'project_id' => 1, 'column_id' => 3, 'owner_id' => 1)));

        $this->assertNotFalse($pd->duplicate(1, array('category', 'action', 'task')));
        $project = $p->getByName('P1 (Clone)');
        $this->assertNotFalse($project);
        $project_id = $project['id'];

        // Check if Swimlanes have NOT been duplicated
        $this->assertCount(0, $s->getAll($project_id));

        // Check if Tasks have been duplicated
        $tasks = $tf->getAll($project_id);

        $this->assertCount(3, $tasks);
        //$this->assertEquals(4, $tasks[0]['id']);
        $this->assertEquals('T1', $tasks[0]['title']);
        //$this->assertEquals(5, $tasks[1]['id']);
        $this->assertEquals('T2', $tasks[1]['title']);
        //$this->assertEquals(6, $tasks[2]['id']);
        $this->assertEquals('T3', $tasks[2]['title']);
    }
}
