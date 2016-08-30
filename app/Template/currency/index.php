<div class="page-header">
    <h2><?= t('Currency rates') ?></h2>
</div>

<?php if (! empty($rates)): ?>

<table class="table-striped">
    <tr>
        <th class="column-35"><?= t('Currency') ?></th>
        <th><?= t('Rate') ?></th>
    </tr>
    <?php foreach ($rates as $rate): ?>
    <tr>
        <td>
            <strong><?= $this->text->e($rate['currency']) ?></strong>
        </td>
        <td>
            <?= n($rate['rate']) ?>
        </td>
    </tr>
    <?php endforeach ?>
</table>

<hr/>
<h3><?= t('Change reference currency') ?></h3>
<?php endif ?>
<form method="post" action="<?= $this->url->href('CurrencyController', 'reference') ?>" autocomplete="off">

    <?= $this->form->csrf() ?>

    <?= $this->form->label(t('Reference currency'), 'application_currency') ?>
    <?= $this->form->select('application_currency', $currencies, $config_values, $errors) ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-blue"><?= t('Save') ?></button>
    </div>
</form>

<hr/>
<h3><?= t('Add a new currency rate') ?></h3>
<form method="post" action="<?= $this->url->href('CurrencyController', 'create') ?>" autocomplete="off">

    <?= $this->form->csrf() ?>

    <?= $this->form->label(t('Currency'), 'currency') ?>
    <?= $this->form->select('currency', $currencies, $values, $errors) ?>

    <?= $this->form->label(t('Rate'), 'rate') ?>
    <?= $this->form->text('rate', $values, $errors, array(), 'form-numeric') ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-blue"><?= t('Save') ?></button>
    </div>
</form>
