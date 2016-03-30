<span class="dropdown">
    <a href="#" class="dropdown-menu"><?= '#'.$task['id'] ?> <i class="fa fa-caret-down"></i></a>
    <ul>
        <li><i class="fa fa-user fa-fw"></i>&nbsp;<?= $this->url->link(t('Change assignee'), 'BoardPopover', 'changeAssignee', array('task_id' => $task['id'], 'project_id' => $task['project_id']), false, 'popover') ?></li>
        <li><i class="fa fa-tag fa-fw"></i>&nbsp;<?= $this->url->link(t('Change category'), 'BoardPopover', 'changeCategory', array('task_id' => $task['id'], 'project_id' => $task['project_id']), false, 'popover') ?></li>
        <li><i class="fa fa-align-left fa-fw"></i>&nbsp;<?= $this->url->link(t('Change description'), 'taskmodification', 'description', array('task_id' => $task['id'], 'project_id' => $task['project_id']), false, 'popover') ?></li>
        <li><i class="fa fa-pencil-square-o fa-fw"></i>&nbsp;<?= $this->url->link(t('Edit this task'), 'taskmodification', 'edit', array('task_id' => $task['id'], 'project_id' => $task['project_id']), false, 'popover') ?></li>
        <li><i class="fa fa-comment-o fa-fw"></i>&nbsp;<?= $this->url->link(t('Add a comment'), 'comment', 'create', array('task_id' => $task['id'], 'project_id' => $task['project_id']), false, 'popover') ?></li>
        <li><i class="fa fa-code-fork fa-fw"></i>&nbsp;<?= $this->url->link(t('Add internal link'), 'TaskInternalLink', 'create', array('task_id' => $task['id'], 'project_id' => $task['project_id']), false, 'popover') ?></li>
        <li><i class="fa fa-external-link fa-fw"></i>&nbsp;<?= $this->url->link(t('Add external link'), 'TaskExternalLink', 'find', array('task_id' => $task['id'], 'project_id' => $task['project_id']), false, 'popover') ?></li>
        <li><i class="fa fa-camera fa-fw"></i>&nbsp;<?= $this->url->link(t('Add a screenshot'), 'BoardPopover', 'screenshot', array('task_id' => $task['id'], 'project_id' => $task['project_id']), false, 'popover') ?></li>
        <?php if ($task['is_active'] == 1): ?>
            <li><i class="fa fa-close fa-fw"></i>&nbsp;<?= $this->url->link(t('Close this task'), 'taskstatus', 'close', array('task_id' => $task['id'], 'project_id' => $task['project_id']), false, 'popover') ?></li>
        <?php else: ?>
            <li><i class="fa fa-check-square-o fa-fw"></i>&nbsp;<?= $this->url->link(t('Open this task'), 'taskstatus', 'open', array('task_id' => $task['id'], 'project_id' => $task['project_id']), false, 'popover') ?></li>
        <?php endif ?>
    </ul>
</span>