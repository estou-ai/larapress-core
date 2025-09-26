<?php
/**
* @var  string $action
 * @var string $adminPost
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
       class="nav-tab">Back</a>
</nav>
<?php
if(@$_SESSION['errors']){
    ?>
    <div class="notice notice-error">
        <h3>Please check this fields:</h3>
        <p><strong><?=implode(', ',$_SESSION['errors']) ?></strong></p>
    </div>
    <?php
    unset($_SESSION['errors']);
}
?>
<?php
if(@$_SESSION['success']){
    ?>
    <div class="notice notice-success is-dismissible">
        <p><strong><?=$_SESSION['success'] ?></strong></p>
    </div>
    <?php
    unset($_SESSION['success']);
}
?>
<div>
    <form method="post" action="<?= $adminPost ?>">
        <input type='hidden' name='action' value='<?=$action?>'/>
        <table class="form-table">
            <tbody>
            <?=$idField?>
            <?php
            /**
             * @var  Form $form
             */
                foreach ($form->getSchema() as $field) {
                    if (!$field->canShow($action))
                        continue
                    ?>
                        <tr class="user-rich-editing-wrap">
                            <th scope="row"><label for="<?=$field->getName()?>"><?=$field->getLabel()?></label></th>
                            <td>
                                <?= $field->render() ?>
                            </td>
                        </tr>
                    <?php
                }
            ?>
            </tbody>
        </table>
        <button type="submit" class="button button-primary">Save</button>
    </form>
</div>

<?php if(isset($javascript)) { ?>
    <?=$javascript?>
<?php } ?>
