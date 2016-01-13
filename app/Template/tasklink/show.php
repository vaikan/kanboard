<?php if (! empty($links)): ?>
<div class="page-header">
    <h2><?= t('Links') ?></h2>
</div>
<table id="links">
    <tr>
        <th class="column-20"><?= t('Label') ?></th>
        <th class="column-30"><?= t('Task') ?></th>
        <th class="column-20"><?= t('Project') ?></th>
        <th><?= t('Column') ?></th>
        <th><?= t('Assignee') ?></th>
        <?php if ($editable): ?>
            <th class="column-5"><?= t('Action') ?></th>
        <?php endif ?>
    </tr>
    <?php foreach ($links as $label => $grouped_links): ?>
        <?php $hide_td = false ?>
        <?php foreach ($grouped_links as $link): ?>
        <tr>
            <?php if (! $hide_td): ?>
                <td rowspan="<?= count($grouped_links) ?>"><?= t('This task') ?> <strong><?= t($label) ?></strong></td>
                <?php $hide_td = true ?>
            <?php endif ?>

            <td>
                <?php if ($is_public): ?>
                    <?= $this->url->link(
                        $this->e('#'.$link['task_id'].' '.$link['title']),
                        'task',
                        'readonly',
                        array('task_id' => $link['task_id'], 'token' => $project['token']),
                        false,
                        $link['is_active'] ? '' : 'task-link-closed'
                    ) ?>
                <?php else: ?>
                    <?= $this->url->link(
                        $this->e('#'.$link['task_id'].' '.$link['title']),
                        'task',
                        'show',
                        array('task_id' => $link['task_id'], 'project_id' => $link['project_id']),
                        false,
                        $link['is_active'] ? '' : 'task-link-closed'
                    ) ?>
                <?php endif ?>

                <br/>

                <?php if (! empty($link['task_time_spent'])): ?>
                    <strong><?= $this->e($link['task_time_spent']).'h' ?></strong> <?= t('spent') ?>
                <?php endif ?>

                <?php if (! empty($link['task_time_estimated'])): ?>
                    <strong><?= $this->e($link['task_time_estimated']).'h' ?></strong> <?= t('estimated') ?>
                <?php endif ?>
            </td>
            <td><?= $this->e($link['project_name']) ?></td>
            <td><?= $this->e($link['column_title']) ?></td>
            <td>
                <?php if (! empty($link['task_assignee_username'])): ?>
                    <?php if ($editable): ?>
                        <?= $this->url->link($this->e($link['task_assignee_name'] ?: $link['task_assignee_username']), 'user', 'show', array('user_id' => $link['task_assignee_id'])) ?>
                    <?php else: ?>
                        <?= $this->e($link['task_assignee_name'] ?: $link['task_assignee_username']) ?>
                    <?php endif ?>
                <?php endif ?>
            </td>
            <?php if ($editable): ?>
            <td>
                <div class="dropdown">
                <a href="#" class="dropdown-menu dropdown-menu-link-icon"><i class="fa fa-cog fa-fw"></i><i class="fa fa-caret-down"></i></a>
                <ul>
                    <li><?= $this->url->link(t('Edit'), 'tasklink', 'edit', array('link_id' => $link['id'], 'task_id' => $task['id'], 'project_id' => $task['project_id'])) ?></li>
                    <li><?= $this->url->link(t('Remove'), 'tasklink', 'confirm', array('link_id' => $link['id'], 'task_id' => $task['id'], 'project_id' => $task['project_id'])) ?></li>
                </ul>
                </div>
            </td>
            <?php endif ?>
        </tr>
        <?php endforeach ?>
    <?php endforeach ?>
</table>

<?php if ($editable && isset($link_label_list)): ?>
    <form action="<?= $this->url->href('tasklink', 'save', array('task_id' => $task['id'], 'project_id' => $task['project_id'])) ?>" method="post" autocomplete="off">

        <?= $this->form->csrf() ?>
        <?= $this->form->hidden('task_id', array('task_id' => $task['id'])) ?>
        <?= $this->form->hidden('opposite_task_id', array()) ?>

        <?= $this->form->select('link_id', $link_label_list, array(), array()) ?>

        <?= $this->form->text(
            'title',
            array(),
            array(),
            array(
                'required',
                'placeholder="'.t('Start to type task title...').'"',
                'title="'.t('Start to type task title...').'"',
                'data-dst-field="opposite_task_id"',
                'data-search-url="'.$this->url->href('TaskHelper', 'autocomplete', array('exclude_task_id' => $task['id'])).'"',
            ),
            'autocomplete') ?>

        <input type="submit" value="<?= t('Add') ?>" class="btn btn-blue"/>
    </form>
<?php endif ?>

<?php endif ?>
