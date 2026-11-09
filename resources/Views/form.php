<?php
/**
* @var  string $action
 * @var string $actionName
 * @var string $adminPost
 * @var string $nonceField
 * @var string $javascript
 * @var string $idField
 */

use Larapress\Components\Form\Form;

?>
<div class="wrap">
    <h1><?php

        echo esc_html( get_admin_page_title() ); ?></h1>
</div>
<nav class="nav-tab-wrapper">
    <a href="#" onclick="window.history.back(); return false;"
       class="nav-tab"><?= esc_html__('Back', 'larapress') ?></a>
</nav>
<?php
$session = &session();

if (!empty($session['errors'])) {
    ?>
    <div class="notice notice-error">
        <h3><?= esc_html__('Please check this fields:', 'larapress') ?></h3>
        <p><strong><?= wp_kses_post(implode(' ', (array) $session['errors'])) ?></strong></p>
    </div>
    <?php
    unset($session['errors']);
}

if (!empty($session['success'])) {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><strong><?= esc_html($session['success']) ?></strong></p>
    </div>
    <?php
    unset($session['success']);
}
?>
<div>
    <form method="post" action="<?= esc_url($adminPost) ?>">
        <input type='hidden' name='action' value='<?= esc_attr($action) ?>'/>
        <?= $nonceField ?>
        <table class="form-table">
            <tbody>
            <?=$idField?>
            <?php
            /**
             * @var  Form $form
             */
                foreach ($form->getSchema() as $field) {
                    if (!$field->canShow($actionName ?? $action))
                        continue
                    ?>
                        <tr class="user-rich-editing-wrap">
                            <th scope="row"><label for="<?= esc_attr($field->getName()) ?>"><?= esc_html($field->getLabel()) ?></label></th>
                            <td>
                                <?= $field->render() ?>
                            </td>
                        </tr>
                    <?php
                }
            ?>
            </tbody>
        </table>
        <button type="submit" class="button button-primary"><?= esc_html__('Save', 'larapress') ?></button>
    </form>
</div>

<?php if(isset($javascript)) { ?>
    <?=$javascript?>
<?php } ?>
