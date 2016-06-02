<div class="page-header">
    <h2><?= t('Enable user') ?></h2>
</div>

<div class="confirm">
    <p class="alert alert-info"><?= t('Do you really want to enable this user: "%s"?', $user['name'] ?: $user['username']) ?></p>

    <div class="form-actions">
        <?= $this->url->link(t('Yes'), 'UserStatusController', 'enable', array('user_id' => $user['id']), true, 'btn btn-red') ?>
        <?= t('or') ?>
        <?= $this->url->link(t('cancel'), 'UserListController', 'show', array(), false, 'close-popover') ?>
    </div>
</div>
