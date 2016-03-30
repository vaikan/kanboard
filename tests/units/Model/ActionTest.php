<?php

require_once __DIR__.'/../Base.php';

use Kanboard\Model\Action;
use Kanboard\Model\Project;
use Kanboard\Model\Task;
use Kanboard\Model\User;
use Kanboard\Model\Column;
use Kanboard\Model\Category;
use Kanboard\Model\ProjectUserRole;
use Kanboard\Core\Security\Role;

class ActionTest extends Base
{
    public function testCreate()
    {
        $projectModel = new Project($this->container);
        $actionModel = new Action($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test')));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_CREATE,
            'action_name' => '\Kanboard\Action\TaskAssignColorColumn',
            'params' => array('column_id' => 1, 'color_id' => 'red'),
        )));
    }

    public function testRemove()
    {
        $projectModel = new Project($this->container);
        $actionModel = new Action($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test')));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_CREATE,
            'action_name' => '\Kanboard\Action\TaskAssignColorColumn',
            'params' => array('column_id' => 1, 'color_id' => 'red'),
        )));

        $this->assertNotEmpty($actionModel->getById(1));
        $this->assertTrue($actionModel->remove(1));
        $this->assertEmpty($actionModel->getById(1));
    }

    public function testGetById()
    {
        $projectModel = new Project($this->container);
        $actionModel = new Action($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test')));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_CREATE,
            'action_name' => '\Kanboard\Action\TaskAssignColorColumn',
            'params' => array('column_id' => 1, 'color_id' => 'red'),
        )));

        $action = $actionModel->getById(1);
        $this->assertNotEmpty($action);
        $this->assertEquals(1, $action['project_id']);
        $this->assertEquals('\Kanboard\Action\TaskAssignColorColumn', $action['action_name']);
        $this->assertEquals(Task::EVENT_CREATE, $action['event_name']);
        $this->assertEquals(array('column_id' => 1, 'color_id' => 'red'), $action['params']);
    }

    public function testGetAll()
    {
        $projectModel = new Project($this->container);
        $actionModel = new Action($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'test2')));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_CREATE,
            'action_name' => '\Kanboard\Action\TaskAssignColorColumn',
            'params' => array('column_id' => 1, 'color_id' => 'red'),
        )));

        $this->assertEquals(2, $actionModel->create(array(
            'project_id' => 2,
            'event_name' => Task::EVENT_MOVE_COLUMN,
            'action_name' => '\Kanboard\Action\TaskAssignColorColumn',
            'params' => array('column_id' => 6, 'color_id' => 'blue'),
        )));

        $actions = $actionModel->getAll();
        $this->assertCount(2, $actions);

        $this->assertEquals(1, $actions[0]['project_id']);
        $this->assertEquals('\Kanboard\Action\TaskAssignColorColumn', $actions[0]['action_name']);
        $this->assertEquals(Task::EVENT_CREATE, $actions[0]['event_name']);
        $this->assertEquals(array('column_id' => 1, 'color_id' => 'red'), $actions[0]['params']);

        $this->assertEquals(2, $actions[1]['project_id']);
        $this->assertEquals('\Kanboard\Action\TaskAssignColorColumn', $actions[1]['action_name']);
        $this->assertEquals(Task::EVENT_MOVE_COLUMN, $actions[1]['event_name']);
        $this->assertEquals(array('column_id' => 6, 'color_id' => 'blue'), $actions[1]['params']);
    }

    public function testGetAllByProject()
    {
        $projectModel = new Project($this->container);
        $actionModel = new Action($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'test2')));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_CREATE,
            'action_name' => '\Kanboard\Action\TaskAssignColorColumn',
            'params' => array('column_id' => 1, 'color_id' => 'red'),
        )));

        $this->assertEquals(2, $actionModel->create(array(
            'project_id' => 2,
            'event_name' => Task::EVENT_MOVE_COLUMN,
            'action_name' => '\Kanboard\Action\TaskAssignColorColumn',
            'params' => array('column_id' => 6, 'color_id' => 'blue'),
        )));

        $actions = $actionModel->getAllByProject(1);
        $this->assertCount(1, $actions);

        $this->assertEquals(1, $actions[0]['project_id']);
        $this->assertEquals('\Kanboard\Action\TaskAssignColorColumn', $actions[0]['action_name']);
        $this->assertEquals(Task::EVENT_CREATE, $actions[0]['event_name']);
        $this->assertEquals(array('column_id' => 1, 'color_id' => 'red'), $actions[0]['params']);


        $actions = $actionModel->getAllByProject(2);
        $this->assertCount(1, $actions);

        $this->assertEquals(2, $actions[0]['project_id']);
        $this->assertEquals('\Kanboard\Action\TaskAssignColorColumn', $actions[0]['action_name']);
        $this->assertEquals(Task::EVENT_MOVE_COLUMN, $actions[0]['event_name']);
        $this->assertEquals(array('column_id' => 6, 'color_id' => 'blue'), $actions[0]['params']);
    }

    public function testGetAllByUser()
    {
        $projectModel = new Project($this->container);
        $projectUserRoleModel = new ProjectUserRole($this->container);
        $userModel = new User($this->container);
        $actionModel = new Action($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'test2')));
        $this->assertEquals(3, $projectModel->create(array('name' => 'test4', 'is_active' => 0)));

        $this->assertEquals(2, $userModel->create(array('username' => 'user1')));
        $this->assertEquals(3, $userModel->create(array('username' => 'user2')));

        $this->assertTrue($projectUserRoleModel->addUser(1, 2, Role::PROJECT_VIEWER));
        $this->assertTrue($projectUserRoleModel->addUser(2, 3, Role::PROJECT_MANAGER));
        $this->assertTrue($projectUserRoleModel->addUser(3, 3, Role::PROJECT_MANAGER));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_CREATE,
            'action_name' => '\Kanboard\Action\TaskAssignColorColumn',
            'params' => array('column_id' => 1, 'color_id' => 'red'),
        )));

        $this->assertEquals(2, $actionModel->create(array(
            'project_id' => 2,
            'event_name' => Task::EVENT_MOVE_COLUMN,
            'action_name' => '\Kanboard\Action\TaskAssignColorColumn',
            'params' => array('column_id' => 6, 'color_id' => 'blue'),
        )));

        $this->assertEquals(3, $actionModel->create(array(
            'project_id' => 3,
            'event_name' => Task::EVENT_MOVE_COLUMN,
            'action_name' => '\Kanboard\Action\TaskAssignColorColumn',
            'params' => array('column_id' => 10, 'color_id' => 'green'),
        )));

        $actions = $actionModel->getAllByUser(1);
        $this->assertCount(0, $actions);

        $actions = $actionModel->getAllByUser(2);
        $this->assertCount(1, $actions);

        $this->assertEquals(1, $actions[0]['project_id']);
        $this->assertEquals('\Kanboard\Action\TaskAssignColorColumn', $actions[0]['action_name']);
        $this->assertEquals(Task::EVENT_CREATE, $actions[0]['event_name']);
        $this->assertEquals(array('column_id' => 1, 'color_id' => 'red'), $actions[0]['params']);

        $actions = $actionModel->getAllByUser(3);
        $this->assertCount(1, $actions);

        $this->assertEquals(2, $actions[0]['project_id']);
        $this->assertEquals('\Kanboard\Action\TaskAssignColorColumn', $actions[0]['action_name']);
        $this->assertEquals(Task::EVENT_MOVE_COLUMN, $actions[0]['event_name']);
        $this->assertEquals(array('column_id' => 6, 'color_id' => 'blue'), $actions[0]['params']);
    }

    public function testDuplicateWithColumnAndColorParameter()
    {
        $projectModel = new Project($this->container);
        $actionModel = new Action($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'test2')));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_CREATE,
            'action_name' => '\Kanboard\Action\TaskAssignColorColumn',
            'params' => array('column_id' => 1, 'color_id' => 'red'),
        )));

        $this->assertTrue($actionModel->duplicate(1, 2));

        $actions = $actionModel->getAllByProject(2);
        $this->assertCount(1, $actions);

        $this->assertEquals(2, $actions[0]['project_id']);
        $this->assertEquals('\Kanboard\Action\TaskAssignColorColumn', $actions[0]['action_name']);
        $this->assertEquals(Task::EVENT_CREATE, $actions[0]['event_name']);
        $this->assertEquals(array('column_id' => 5, 'color_id' => 'red'), $actions[0]['params']);
    }

    public function testDuplicateWithColumnsParameter()
    {
        $projectModel = new Project($this->container);
        $actionModel = new Action($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'test2')));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_CREATE,
            'action_name' => '\Kanboard\Action\TaskAssignColorColumn',
            'params' => array('src_column_id' => 1, 'dst_column_id' => 2, 'dest_column_id' => 3),
        )));

        $this->assertTrue($actionModel->duplicate(1, 2));

        $actions = $actionModel->getAllByProject(2);
        $this->assertCount(1, $actions);

        $this->assertEquals(2, $actions[0]['project_id']);
        $this->assertEquals('\Kanboard\Action\TaskAssignColorColumn', $actions[0]['action_name']);
        $this->assertEquals(Task::EVENT_CREATE, $actions[0]['event_name']);
        $this->assertEquals(array('src_column_id' => 5, 'dst_column_id' => 6, 'dest_column_id' => 7), $actions[0]['params']);
    }

    public function testDuplicateWithColumnParameterNotfound()
    {
        $projectModel = new Project($this->container);
        $actionModel = new Action($this->container);
        $columnModel = new Column($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'test2')));

        $this->assertTrue($columnModel->update(2, 'My unique column'));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_CREATE,
            'action_name' => '\Kanboard\Action\TaskAssignColorColumn',
            'params' => array('column_id' => 1, 'color_id' => 'red'),
        )));

        $this->assertEquals(2, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_MOVE_COLUMN,
            'action_name' => '\Kanboard\Action\TaskAssignColorColumn',
            'params' => array('column_id' => 2, 'color_id' => 'green'),
        )));

        $this->assertTrue($actionModel->duplicate(1, 2));

        $actions = $actionModel->getAllByProject(2);
        $this->assertCount(1, $actions);

        $this->assertEquals(2, $actions[0]['project_id']);
        $this->assertEquals('\Kanboard\Action\TaskAssignColorColumn', $actions[0]['action_name']);
        $this->assertEquals(Task::EVENT_CREATE, $actions[0]['event_name']);
        $this->assertEquals(array('column_id' => 5, 'color_id' => 'red'), $actions[0]['params']);
    }

    public function testDuplicateWithProjectParameter()
    {
        $projectModel = new Project($this->container);
        $actionModel = new Action($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'test2')));
        $this->assertEquals(3, $projectModel->create(array('name' => 'test2')));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_CLOSE,
            'action_name' => '\Kanboard\Action\TaskDuplicateAnotherProject',
            'params' => array('column_id' => 1, 'project_id' => 3),
        )));

        $this->assertTrue($actionModel->duplicate(1, 2));

        $actions = $actionModel->getAllByProject(2);
        $this->assertCount(1, $actions);

        $this->assertEquals(2, $actions[0]['project_id']);
        $this->assertEquals('\Kanboard\Action\TaskDuplicateAnotherProject', $actions[0]['action_name']);
        $this->assertEquals(Task::EVENT_CLOSE, $actions[0]['event_name']);
        $this->assertEquals(array('column_id' => 5, 'project_id' => 3), $actions[0]['params']);
    }

    public function testDuplicateWithProjectParameterIdenticalToDestination()
    {
        $projectModel = new Project($this->container);
        $actionModel = new Action($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'test2')));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_CLOSE,
            'action_name' => '\Kanboard\Action\TaskDuplicateAnotherProject',
            'params' => array('column_id' => 1, 'project_id' => 2),
        )));

        $this->assertTrue($actionModel->duplicate(1, 2));

        $actions = $actionModel->getAllByProject(2);
        $this->assertCount(0, $actions);
    }

    public function testDuplicateWithUserParameter()
    {
        $projectUserRoleModel = new ProjectUserRole($this->container);
        $userModel = new User($this->container);
        $projectModel = new Project($this->container);
        $actionModel = new Action($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'test2')));

        $this->assertEquals(2, $userModel->create(array('username' => 'user1')));

        $this->assertTrue($projectUserRoleModel->addUser(1, 2, Role::PROJECT_MEMBER));
        $this->assertTrue($projectUserRoleModel->addUser(2, 2, Role::PROJECT_MEMBER));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_MOVE_COLUMN,
            'action_name' => '\Kanboard\Action\TaskAssignSpecificUser',
            'params' => array('column_id' => 1, 'user_id' => 2),
        )));

        $this->assertTrue($actionModel->duplicate(1, 2));

        $actions = $actionModel->getAllByProject(2);
        $this->assertCount(1, $actions);

        $this->assertEquals(2, $actions[0]['project_id']);
        $this->assertEquals('\Kanboard\Action\TaskAssignSpecificUser', $actions[0]['action_name']);
        $this->assertEquals(Task::EVENT_MOVE_COLUMN, $actions[0]['event_name']);
        $this->assertEquals(array('column_id' => 5, 'user_id' => 2), $actions[0]['params']);
    }

    public function testDuplicateWithUserParameterButNotAssignable()
    {
        $projectUserRoleModel = new ProjectUserRole($this->container);
        $userModel = new User($this->container);
        $projectModel = new Project($this->container);
        $actionModel = new Action($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'test2')));

        $this->assertEquals(2, $userModel->create(array('username' => 'user1')));

        $this->assertTrue($projectUserRoleModel->addUser(1, 2, Role::PROJECT_MEMBER));
        $this->assertTrue($projectUserRoleModel->addUser(2, 2, Role::PROJECT_VIEWER));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_MOVE_COLUMN,
            'action_name' => '\Kanboard\Action\TaskAssignSpecificUser',
            'params' => array('column_id' => 1, 'user_id' => 2),
        )));

        $this->assertTrue($actionModel->duplicate(1, 2));

        $actions = $actionModel->getAllByProject(2);
        $this->assertCount(0, $actions);
    }

    public function testDuplicateWithUserParameterButNotAvailable()
    {
        $projectUserRoleModel = new ProjectUserRole($this->container);
        $userModel = new User($this->container);
        $projectModel = new Project($this->container);
        $actionModel = new Action($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'test2')));

        $this->assertEquals(2, $userModel->create(array('username' => 'user1')));

        $this->assertTrue($projectUserRoleModel->addUser(1, 2, Role::PROJECT_MEMBER));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_MOVE_COLUMN,
            'action_name' => '\Kanboard\Action\TaskAssignSpecificUser',
            'params' => array('column_id' => 1, 'owner_id' => 2),
        )));

        $this->assertTrue($actionModel->duplicate(1, 2));

        $actions = $actionModel->getAllByProject(2);
        $this->assertCount(0, $actions);
    }

    public function testDuplicateWithCategoryParameter()
    {
        $projectModel = new Project($this->container);
        $actionModel = new Action($this->container);
        $categoryModel = new Category($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'test2')));

        $this->assertEquals(1, $categoryModel->create(array('name' => 'c1', 'project_id' => 1)));
        $this->assertEquals(2, $categoryModel->create(array('name' => 'c1', 'project_id' => 2)));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_CREATE_UPDATE,
            'action_name' => '\Kanboard\Action\TaskAssignColorCategory',
            'params' => array('column_id' => 1, 'category_id' => 1),
        )));

        $this->assertTrue($actionModel->duplicate(1, 2));

        $actions = $actionModel->getAllByProject(2);
        $this->assertCount(1, $actions);

        $this->assertEquals(2, $actions[0]['project_id']);
        $this->assertEquals('\Kanboard\Action\TaskAssignColorCategory', $actions[0]['action_name']);
        $this->assertEquals(Task::EVENT_CREATE_UPDATE, $actions[0]['event_name']);
        $this->assertEquals(array('column_id' => 5, 'category_id' => 2), $actions[0]['params']);
    }

    public function testDuplicateWithCategoryParameterButDifferentName()
    {
        $projectModel = new Project($this->container);
        $actionModel = new Action($this->container);
        $categoryModel = new Category($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'test2')));

        $this->assertEquals(1, $categoryModel->create(array('name' => 'c1', 'project_id' => 1)));
        $this->assertEquals(2, $categoryModel->create(array('name' => 'c2', 'project_id' => 2)));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_CREATE_UPDATE,
            'action_name' => '\Kanboard\Action\TaskAssignColorCategory',
            'params' => array('column_id' => 1, 'category_id' => 1),
        )));

        $this->assertTrue($actionModel->duplicate(1, 2));

        $actions = $actionModel->getAllByProject(2);
        $this->assertCount(0, $actions);
    }

    public function testDuplicateWithCategoryParameterButNotFound()
    {
        $projectModel = new Project($this->container);
        $actionModel = new Action($this->container);
        $categoryModel = new Category($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'test1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'test2')));

        $this->assertEquals(1, $categoryModel->create(array('name' => 'c1', 'project_id' => 1)));

        $this->assertEquals(1, $actionModel->create(array(
            'project_id' => 1,
            'event_name' => Task::EVENT_CREATE_UPDATE,
            'action_name' => '\Kanboard\Action\TaskAssignColorCategory',
            'params' => array('column_id' => 1, 'category_id' => 1),
        )));

        $this->assertTrue($actionModel->duplicate(1, 2));

        $actions = $actionModel->getAllByProject(2);
        $this->assertCount(0, $actions);
    }
}
