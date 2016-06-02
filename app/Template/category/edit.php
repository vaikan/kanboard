<div class="page-header">
    <h2><?= t('Category modification for the project "%s"', $project['name']) ?></h2>
</div>

<form class="popover-form" method="post" action="<?= $this->url->href('CategoryController', 'update', array('project_id' => $project['id'], 'category_id' => $values['id'])) ?>" autocomplete="off">

    <?= $this->form->csrf() ?>

    <?= $this->form->hidden('id', $values) ?>
    <?= $this->form->hidden('project_id', $values) ?>

    <?= $this->form->label(t('Category Name'), 'name') ?>
    <?= $this->form->text('name', $values, $errors, array('autofocus', 'required', 'maxlength="50"')) ?>

    <?= $this->form->label(t('Description'), 'description') ?>
    <?= $this->form->textarea('description', $values, $errors, array(), 'markdown-editor') ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-blue"><?= t('Save') ?></button>
        <?= t('or') ?>
        <?= $this->url->link(t('cancel'), 'CategoryController', 'index', array('project_id' => $project['id']), false, 'close-popover') ?>
    </div>
</form>
