<section id="main">
    <div class="page-header">
        <ul>
            <li>
                <i class="fa fa-folder fa-fw"></i>
                <?= $this->url->link(t('Projects list'), 'ProjectListController', 'show') ?>
            </li>
            <?php if ($this->user->hasAccess('ProjectGanttController', 'show')): ?>
                <li>
                    <i class="fa fa-sliders fa-fw"></i>
                    <?= $this->url->link(t('Projects Gantt chart'), 'ProjectGanttController', 'show') ?>
                </li>
            <?php endif ?>
        </ul>
    </div>
    <section class="sidebar-container">

        <?= $this->render($sidebar_template, array('users' => $users, 'filter' => $filter)) ?>

        <div class="sidebar-content">
            <div class="page-header">
                <h2><?= $this->text->e($title) ?></h2>
            </div>
            <?= $content_for_sublayout ?>
        </div>
    </section>
</section>
