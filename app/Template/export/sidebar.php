<div class="sidebar">
    <h2><?= t('Exports') ?></h2>
    <ul>
        <li <?= $this->app->getRouterAction() === 'tasks' ? 'class="active"' : '' ?>>
            <?= $this->url->link(t('Tasks'), 'export', 'tasks', array('project_id' => $project['id'])) ?>
        </li>
        <li <?= $this->app->getRouterAction() === 'subtasks' ? 'class="active"' : '' ?>>
            <?= $this->url->link(t('Subtasks'), 'export', 'subtasks', array('project_id' => $project['id'])) ?>
        </li>
        <li <?= $this->app->getRouterAction() === 'transitions' ? 'class="active"' : '' ?>>
            <?= $this->url->link(t('Task transitions'), 'export', 'transitions', array('project_id' => $project['id'])) ?>
        </li>
        <li <?= $this->app->getRouterAction() === 'summary' ? 'class="active"' : '' ?>>
            <?= $this->url->link(t('Daily project summary'), 'export', 'summary', array('project_id' => $project['id'])) ?>
        </li>
        <?= $this->hook->render('template:export:sidebar') ?>
    </ul>
</div>