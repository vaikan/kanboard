<?php

require_once __DIR__.'/../Base.php';

use Kanboard\Model\Project;
use Kanboard\Model\User;
use Kanboard\Model\Group;
use Kanboard\Model\GroupMember;
use Kanboard\Model\ProjectGroupRole;
use Kanboard\Model\ProjectUserRole;
use Kanboard\Model\ProjectPermission;
use Kanboard\Core\Security\Role;

class ProjectUserRoleTest extends Base
{
    public function testAddUser()
    {
        $projectModel = new Project($this->container);
        $userRoleModel = new ProjectUserRole($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'Test')));

        $this->assertTrue($userRoleModel->addUser(1, 1, Role::PROJECT_VIEWER));
        $this->assertFalse($userRoleModel->addUser(1, 1, Role::PROJECT_VIEWER));

        $users = $userRoleModel->getUsers(1);
        $this->assertCount(1, $users);
        $this->assertEquals(1, $users[0]['id']);
        $this->assertEquals('admin', $users[0]['username']);
        $this->assertEquals('', $users[0]['name']);
        $this->assertEquals(Role::PROJECT_VIEWER, $users[0]['role']);
    }

    public function testRemoveUser()
    {
        $projectModel = new Project($this->container);
        $userRoleModel = new ProjectUserRole($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'Test')));

        $this->assertTrue($userRoleModel->addUser(1, 1, Role::PROJECT_MEMBER));
        $this->assertTrue($userRoleModel->removeUser(1, 1));
        $this->assertFalse($userRoleModel->removeUser(1, 1));

        $this->assertEmpty($userRoleModel->getUsers(1));
    }

    public function testChangeRole()
    {
        $projectModel = new Project($this->container);
        $userRoleModel = new ProjectUserRole($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'Test')));

        $this->assertTrue($userRoleModel->addUser(1, 1, Role::PROJECT_VIEWER));
        $this->assertTrue($userRoleModel->changeUserRole(1, 1, Role::PROJECT_MANAGER));

        $users = $userRoleModel->getUsers(1);
        $this->assertCount(1, $users);
        $this->assertEquals(1, $users[0]['id']);
        $this->assertEquals('admin', $users[0]['username']);
        $this->assertEquals('', $users[0]['name']);
        $this->assertEquals(Role::PROJECT_MANAGER, $users[0]['role']);
    }

    public function testGetRole()
    {
        $projectModel = new Project($this->container);
        $userRoleModel = new ProjectUserRole($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'Test')));
        $this->assertEmpty($userRoleModel->getUserRole(1, 1));

        $this->assertTrue($userRoleModel->addUser(1, 1, Role::PROJECT_VIEWER));
        $this->assertEquals(Role::PROJECT_VIEWER, $userRoleModel->getUserRole(1, 1));

        $this->assertTrue($userRoleModel->changeUserRole(1, 1, Role::PROJECT_MEMBER));
        $this->assertEquals(Role::PROJECT_MEMBER, $userRoleModel->getUserRole(1, 1));

        $this->assertTrue($userRoleModel->changeUserRole(1, 1, Role::PROJECT_MANAGER));
        $this->assertEquals(Role::PROJECT_MANAGER, $userRoleModel->getUserRole(1, 1));

        $this->assertEquals('', $userRoleModel->getUserRole(1, 2));
    }

    public function testGetRoleWithGroups()
    {
        $projectModel = new Project($this->container);
        $groupModel = new Group($this->container);
        $groupRoleModel = new ProjectGroupRole($this->container);
        $groupMemberModel = new GroupMember($this->container);
        $userRoleModel = new ProjectUserRole($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'Test')));
        $this->assertEquals(1, $groupModel->create('Group A'));

        $this->assertTrue($groupMemberModel->addUser(1, 1));
        $this->assertTrue($groupRoleModel->addGroup(1, 1, Role::PROJECT_VIEWER));

        $this->assertEquals(Role::PROJECT_VIEWER, $userRoleModel->getUserRole(1, 1));
        $this->assertEquals('', $userRoleModel->getUserRole(1, 2));
    }

    public function testGetAssignableUsersWithDisabledUsers()
    {
        $projectModel = new Project($this->container);
        $userModel = new User($this->container);
        $userRoleModel = new ProjectUserRole($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'Test')));
        $this->assertEquals(2, $userModel->create(array('username' => 'user1', 'name' => 'User1')));
        $this->assertEquals(3, $userModel->create(array('username' => 'user2', 'name' => 'User2')));

        $this->assertTrue($userRoleModel->addUser(1, 1, Role::PROJECT_MEMBER));
        $this->assertTrue($userRoleModel->addUser(1, 2, Role::PROJECT_MEMBER));
        $this->assertTrue($userRoleModel->addUser(1, 3, Role::PROJECT_MEMBER));

        $users = $userRoleModel->getAssignableUsers(1);
        $this->assertCount(3, $users);

        $this->assertEquals('admin', $users[1]);
        $this->assertEquals('User1', $users[2]);
        $this->assertEquals('User2', $users[3]);

        $this->assertTrue($userModel->disable(2));

        $users = $userRoleModel->getAssignableUsers(1);
        $this->assertCount(2, $users);

        $this->assertEquals('admin', $users[1]);
        $this->assertEquals('User2', $users[3]);
    }

    public function testGetAssignableUsersWithoutGroups()
    {
        $projectModel = new Project($this->container);
        $userModel = new User($this->container);
        $userRoleModel = new ProjectUserRole($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'Test')));
        $this->assertEquals(2, $userModel->create(array('username' => 'user1', 'name' => 'User1')));
        $this->assertEquals(3, $userModel->create(array('username' => 'user2', 'name' => 'User2')));

        $this->assertTrue($userRoleModel->addUser(1, 1, Role::PROJECT_MEMBER));
        $this->assertTrue($userRoleModel->addUser(1, 2, Role::PROJECT_MANAGER));
        $this->assertTrue($userRoleModel->addUser(1, 3, Role::PROJECT_VIEWER));

        $users = $userRoleModel->getAssignableUsers(1);
        $this->assertCount(2, $users);

        $this->assertEquals('admin', $users[1]);
        $this->assertEquals('User1', $users[2]);
    }

    public function testGetAssignableUsersWithGroups()
    {
        $projectModel = new Project($this->container);
        $userModel = new User($this->container);
        $groupModel = new Group($this->container);
        $userRoleModel = new ProjectUserRole($this->container);
        $groupRoleModel = new ProjectGroupRole($this->container);
        $groupMemberModel = new GroupMember($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'Test')));

        $this->assertEquals(2, $userModel->create(array('username' => 'user1', 'name' => 'User1')));
        $this->assertEquals(3, $userModel->create(array('username' => 'user2', 'name' => 'User2')));
        $this->assertEquals(4, $userModel->create(array('username' => 'user3', 'name' => 'User3')));
        $this->assertEquals(5, $userModel->create(array('username' => 'user4', 'name' => 'User4')));

        $this->assertTrue($userRoleModel->addUser(1, 1, Role::PROJECT_MEMBER));
        $this->assertTrue($userRoleModel->addUser(1, 2, Role::PROJECT_MANAGER));
        $this->assertTrue($userRoleModel->addUser(1, 3, Role::PROJECT_VIEWER));

        $this->assertEquals(1, $groupModel->create('Group A'));
        $this->assertEquals(2, $groupModel->create('Group B'));

        $this->assertTrue($groupMemberModel->addUser(1, 4));
        $this->assertTrue($groupMemberModel->addUser(2, 5));

        $this->assertTrue($groupRoleModel->addGroup(1, 1, Role::PROJECT_VIEWER));
        $this->assertTrue($groupRoleModel->addGroup(1, 2, Role::PROJECT_MEMBER));

        $users = $userRoleModel->getAssignableUsers(1);
        $this->assertCount(3, $users);

        $this->assertEquals('admin', $users[1]);
        $this->assertEquals('User1', $users[2]);
        $this->assertEquals('User4', $users[5]);
    }

    public function testGetAssignableUsersList()
    {
        $projectModel = new Project($this->container);
        $userModel = new User($this->container);
        $userRoleModel = new ProjectUserRole($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'Test1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'Test2')));

        $this->assertEquals(2, $userModel->create(array('username' => 'user1', 'name' => 'User1')));
        $this->assertEquals(3, $userModel->create(array('username' => 'user2', 'name' => 'User2')));

        $this->assertTrue($userRoleModel->addUser(2, 1, Role::PROJECT_MEMBER));
        $this->assertTrue($userRoleModel->addUser(1, 1, Role::PROJECT_MEMBER));
        $this->assertTrue($userRoleModel->addUser(1, 2, Role::PROJECT_MANAGER));
        $this->assertTrue($userRoleModel->addUser(1, 3, Role::PROJECT_VIEWER));

        $users = $userRoleModel->getAssignableUsersList(1);
        $this->assertCount(3, $users);

        $this->assertEquals('Unassigned', $users[0]);
        $this->assertEquals('admin', $users[1]);
        $this->assertEquals('User1', $users[2]);

        $users = $userRoleModel->getAssignableUsersList(1, true, true, true);
        $this->assertCount(4, $users);

        $this->assertEquals('Unassigned', $users[0]);
        $this->assertEquals('Everybody', $users[-1]);
        $this->assertEquals('admin', $users[1]);
        $this->assertEquals('User1', $users[2]);

        $users = $userRoleModel->getAssignableUsersList(2, true, true, true);
        $this->assertCount(1, $users);

        $this->assertEquals('admin', $users[1]);
    }

    public function testGetAssignableUsersWithEverybodyAllowed()
    {
        $projectModel = new Project($this->container);
        $userModel = new User($this->container);
        $userRoleModel = new ProjectUserRole($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'Test', 'is_everybody_allowed' => 1)));

        $this->assertEquals(2, $userModel->create(array('username' => 'user1', 'name' => 'User1')));
        $this->assertEquals(3, $userModel->create(array('username' => 'user2', 'name' => 'User2')));
        $this->assertEquals(4, $userModel->create(array('username' => 'user3', 'name' => 'User3')));
        $this->assertEquals(5, $userModel->create(array('username' => 'user4', 'name' => 'User4')));

        $users = $userRoleModel->getAssignableUsers(1);
        $this->assertCount(5, $users);

        $this->assertEquals('admin', $users[1]);
        $this->assertEquals('User1', $users[2]);
        $this->assertEquals('User2', $users[3]);
        $this->assertEquals('User3', $users[4]);
        $this->assertEquals('User4', $users[5]);
    }

    public function testGetAssignableUsersWithDisabledUsersAndEverybodyAllowed()
    {
        $projectModel = new Project($this->container);
        $projectPermission = new ProjectPermission($this->container);
        $userModel = new User($this->container);
        $userRoleModel = new ProjectUserRole($this->container);

        $this->assertEquals(2, $userModel->create(array('username' => 'user1', 'name' => 'User1')));
        $this->assertEquals(3, $userModel->create(array('username' => 'user2', 'name' => 'User2')));

        $this->assertEquals(1, $projectModel->create(array('name' => 'Project 1', 'is_everybody_allowed' => 1)));

        $this->assertTrue($projectPermission->isEverybodyAllowed(1));

        $users = $userRoleModel->getAssignableUsers(1);
        $this->assertCount(3, $users);

        $this->assertEquals('admin', $users[1]);
        $this->assertEquals('User1', $users[2]);
        $this->assertEquals('User2', $users[3]);

        $this->assertTrue($userModel->disable(2));

        $users = $userRoleModel->getAssignableUsers(1);
        $this->assertCount(2, $users);

        $this->assertEquals('admin', $users[1]);
        $this->assertEquals('User2', $users[3]);
    }

    public function testGetProjectsByUser()
    {
        $userModel = new User($this->container);
        $projectModel = new Project($this->container);
        $groupModel = new Group($this->container);
        $groupMemberModel = new GroupMember($this->container);
        $groupRoleModel = new ProjectGroupRole($this->container);
        $userRoleModel = new ProjectUserRole($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'Project 1')));
        $this->assertEquals(2, $projectModel->create(array('name' => 'Project 2')));

        $this->assertEquals(2, $userModel->create(array('username' => 'user 1', 'name' => 'User #1')));
        $this->assertEquals(3, $userModel->create(array('username' => 'user 2')));
        $this->assertEquals(4, $userModel->create(array('username' => 'user 3')));
        $this->assertEquals(5, $userModel->create(array('username' => 'user 4')));
        $this->assertEquals(6, $userModel->create(array('username' => 'user 5', 'name' => 'User #5')));
        $this->assertEquals(7, $userModel->create(array('username' => 'user 6')));

        $this->assertEquals(1, $groupModel->create('Group C'));
        $this->assertEquals(2, $groupModel->create('Group B'));
        $this->assertEquals(3, $groupModel->create('Group A'));

        $this->assertTrue($groupMemberModel->addUser(1, 4));
        $this->assertTrue($groupMemberModel->addUser(2, 5));
        $this->assertTrue($groupMemberModel->addUser(3, 3));
        $this->assertTrue($groupMemberModel->addUser(3, 2));

        $this->assertTrue($groupRoleModel->addGroup(1, 1, Role::PROJECT_VIEWER));
        $this->assertTrue($groupRoleModel->addGroup(2, 2, Role::PROJECT_MEMBER));
        $this->assertTrue($groupRoleModel->addGroup(1, 3, Role::PROJECT_MANAGER));

        $this->assertTrue($userRoleModel->addUser(1, 6, Role::PROJECT_MANAGER));
        $this->assertTrue($userRoleModel->addUser(2, 6, Role::PROJECT_MEMBER));
        $this->assertTrue($userRoleModel->addUser(2, 7, Role::PROJECT_MEMBER));

        $projects = $userRoleModel->getProjectsByUser(2);
        $this->assertCount(1, $projects);
        $this->assertEquals('Project 1', $projects[1]);

        $projects = $userRoleModel->getProjectsByUser(3);
        $this->assertCount(1, $projects);
        $this->assertEquals('Project 1', $projects[1]);

        $projects = $userRoleModel->getProjectsByUser(4);
        $this->assertCount(1, $projects);
        $this->assertEquals('Project 1', $projects[1]);

        $projects = $userRoleModel->getProjectsByUser(5);
        $this->assertCount(1, $projects);
        $this->assertEquals('Project 2', $projects[2]);

        $projects = $userRoleModel->getProjectsByUser(6);
        $this->assertCount(2, $projects);
        $this->assertEquals('Project 1', $projects[1]);
        $this->assertEquals('Project 2', $projects[2]);

        $projects = $userRoleModel->getProjectsByUser(7);
        $this->assertCount(1, $projects);
        $this->assertEquals('Project 2', $projects[2]);
    }

    public function testGetActiveProjectsByUser()
    {
        $userModel = new User($this->container);
        $projectModel = new Project($this->container);
        $groupModel = new Group($this->container);
        $groupMemberModel = new GroupMember($this->container);
        $groupRoleModel = new ProjectGroupRole($this->container);
        $userRoleModel = new ProjectUserRole($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'Project 1', 'is_active' => 0)));
        $this->assertEquals(2, $projectModel->create(array('name' => 'Project 2')));

        $this->assertEquals(2, $userModel->create(array('username' => 'user 1', 'name' => 'User #1')));
        $this->assertEquals(3, $userModel->create(array('username' => 'user 2')));
        $this->assertEquals(4, $userModel->create(array('username' => 'user 3')));
        $this->assertEquals(5, $userModel->create(array('username' => 'user 4')));
        $this->assertEquals(6, $userModel->create(array('username' => 'user 5', 'name' => 'User #5')));
        $this->assertEquals(7, $userModel->create(array('username' => 'user 6')));

        $this->assertEquals(1, $groupModel->create('Group C'));
        $this->assertEquals(2, $groupModel->create('Group B'));
        $this->assertEquals(3, $groupModel->create('Group A'));

        $this->assertTrue($groupMemberModel->addUser(1, 4));
        $this->assertTrue($groupMemberModel->addUser(2, 5));
        $this->assertTrue($groupMemberModel->addUser(3, 3));
        $this->assertTrue($groupMemberModel->addUser(3, 2));

        $this->assertTrue($groupRoleModel->addGroup(1, 1, Role::PROJECT_VIEWER));
        $this->assertTrue($groupRoleModel->addGroup(2, 2, Role::PROJECT_MEMBER));
        $this->assertTrue($groupRoleModel->addGroup(1, 3, Role::PROJECT_MANAGER));

        $this->assertTrue($userRoleModel->addUser(1, 6, Role::PROJECT_MANAGER));
        $this->assertTrue($userRoleModel->addUser(2, 6, Role::PROJECT_MEMBER));
        $this->assertTrue($userRoleModel->addUser(2, 7, Role::PROJECT_MEMBER));

        $projects = $userRoleModel->getProjectsByUser(2, array(Project::ACTIVE));
        $this->assertCount(0, $projects);

        $projects = $userRoleModel->getProjectsByUser(3, array(Project::ACTIVE));
        $this->assertCount(0, $projects);

        $projects = $userRoleModel->getProjectsByUser(4, array(Project::ACTIVE));
        $this->assertCount(0, $projects);

        $projects = $userRoleModel->getProjectsByUser(5, array(Project::ACTIVE));
        $this->assertCount(1, $projects);
        $this->assertEquals('Project 2', $projects[2]);

        $projects = $userRoleModel->getProjectsByUser(6, array(Project::ACTIVE));
        $this->assertCount(1, $projects);
        $this->assertEquals('Project 2', $projects[2]);

        $projects = $userRoleModel->getProjectsByUser(7, array(Project::ACTIVE));
        $this->assertCount(1, $projects);
        $this->assertEquals('Project 2', $projects[2]);
    }

    public function testGetInactiveProjectsByUser()
    {
        $userModel = new User($this->container);
        $projectModel = new Project($this->container);
        $groupModel = new Group($this->container);
        $groupMemberModel = new GroupMember($this->container);
        $groupRoleModel = new ProjectGroupRole($this->container);
        $userRoleModel = new ProjectUserRole($this->container);

        $this->assertEquals(1, $projectModel->create(array('name' => 'Project 1', 'is_active' => 0)));
        $this->assertEquals(2, $projectModel->create(array('name' => 'Project 2')));

        $this->assertEquals(2, $userModel->create(array('username' => 'user 1', 'name' => 'User #1')));
        $this->assertEquals(3, $userModel->create(array('username' => 'user 2')));
        $this->assertEquals(4, $userModel->create(array('username' => 'user 3')));
        $this->assertEquals(5, $userModel->create(array('username' => 'user 4')));
        $this->assertEquals(6, $userModel->create(array('username' => 'user 5', 'name' => 'User #5')));
        $this->assertEquals(7, $userModel->create(array('username' => 'user 6')));

        $this->assertEquals(1, $groupModel->create('Group C'));
        $this->assertEquals(2, $groupModel->create('Group B'));
        $this->assertEquals(3, $groupModel->create('Group A'));

        $this->assertTrue($groupMemberModel->addUser(1, 4));
        $this->assertTrue($groupMemberModel->addUser(2, 5));
        $this->assertTrue($groupMemberModel->addUser(3, 3));
        $this->assertTrue($groupMemberModel->addUser(3, 2));

        $this->assertTrue($groupRoleModel->addGroup(1, 1, Role::PROJECT_VIEWER));
        $this->assertTrue($groupRoleModel->addGroup(2, 2, Role::PROJECT_MEMBER));
        $this->assertTrue($groupRoleModel->addGroup(1, 3, Role::PROJECT_MANAGER));

        $this->assertTrue($userRoleModel->addUser(1, 6, Role::PROJECT_MANAGER));
        $this->assertTrue($userRoleModel->addUser(2, 6, Role::PROJECT_MEMBER));
        $this->assertTrue($userRoleModel->addUser(2, 7, Role::PROJECT_MEMBER));

        $projects = $userRoleModel->getProjectsByUser(2, array(Project::INACTIVE));
        $this->assertCount(1, $projects);
        $this->assertEquals('Project 1', $projects[1]);

        $projects = $userRoleModel->getProjectsByUser(3, array(Project::INACTIVE));
        $this->assertCount(1, $projects);
        $this->assertEquals('Project 1', $projects[1]);

        $projects = $userRoleModel->getProjectsByUser(4, array(Project::INACTIVE));
        $this->assertCount(1, $projects);
        $this->assertEquals('Project 1', $projects[1]);

        $projects = $userRoleModel->getProjectsByUser(5, array(Project::INACTIVE));
        $this->assertCount(0, $projects);

        $projects = $userRoleModel->getProjectsByUser(6, array(Project::INACTIVE));
        $this->assertCount(1, $projects);
        $this->assertEquals('Project 1', $projects[1]);

        $projects = $userRoleModel->getProjectsByUser(7, array(Project::INACTIVE));
        $this->assertCount(0, $projects);
    }
}
