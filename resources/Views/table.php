<?php
/**
 * @var array $columns
 * @var array $data
 */
function renderColumns($columns)
{
    ?>
    <?php
    foreach ($columns as $column) {
        /**
         * @var \App\Lib\Components\Table\Column $column
         */
        $class = 'manage-column column-title column-primary';
        if ($column->getIsSortable()) {
            $order = array_get($_GET, 'order');
            $orderBy = array_get($_GET, 'orderBy');
            if ($orderBy === $column->getName()) {
                $order = $order === 'asc' ? 'desc' : 'asc';
            } else {
                $order = 'desc';
            }

            $sorted = array_get($_GET, 'orderBy') == $column->getName();

            if ($sorted) {
                $class .= ' sorted' . ' ' . $order;
            } else {
                $class .= ' sortable';
            }

        }

        ?>
        <th scope="col" id="<?= esc_attr($column->getName()) ?>" class="<?= esc_attr($class) ?>">
            <?php if ($column->getIsSortable()){ ?>
            <a href="<?= esc_url(querystring(get_current_page(), ["orderBy" => $column->getName(), "order" => $order])); ?>">
                <?php } ?>
                <span><?= esc_html($column->getLabel()) ?></span>
                <?php if ($column->getIsSortable()) { ?>
                    <span class="sorting-indicators">
                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                    <span class="sorting-indicator desc selected" aria-hidden="true"></span>
                </span>
                <?php } ?>
                <?php if ($column->getIsSortable()){ ?>
            </a>
        <?php } ?>
        </th>
        <?php
    }
    ?>
    <?php
}

?>

<style>
    .updates-table tbody td.check-column, .widefat tbody th.check-column, .widefat tfoot td.check-column, .widefat thead td.check-column {
        padding: 11px 0 0 0px;
    }

    .widefat .check-column {
        width: 2.2em;
        padding: 17px 0 14px;
        vertical-align: top;
    }

    .order-status.status-completed {
        background: #c8d7e1;
        color: #2e4453;
    }

    .order-status {
        display: inline-flex;
        line-height: 2.5em;
        color: #777;
        background: #e5e5e5;
        border-radius: 4px;
        border-bottom: 1px solid rgba(0, 0, 0, .05);
        margin: -.25em 0;
        cursor: inherit !important;
        white-space: nowrap;
        max-width: 100%;
        padding-left: 10px;
        padding-right: 10px;
    }
</style>

<div class="wrap">
    <h1><?php

        echo esc_html(get_admin_page_title()); ?></h1>


    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
        <tr>
            <?php renderColumns($columns) ?>
        </tr>
        </thead>

        <tbody id="the-list">
        <?php foreach ($data as $entry) { ?>
            <tr class="iedit author-self level-0 post-1 type-post status-publish format-standard hentry category-uncategorized">
                <?php foreach ($columns as $column) {
                    ?>
                    <th scope="row">
                        <?= $column->getRenderedValue($entry); ?>
                    </th>
                    <?php
                }
                ?>
            </tr>
        <?php } ?>
        </tbody>

        <tfoot>
        <tr>
            <?php renderColumns($columns) ?>
        </tr>
        </tfoot>

    </table>

    <?php if (isset($table) && $table->isPaginated()) { ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php
                    printf(
                        esc_html(_n('%s item', '%s items', $table->getTotal(), 'larapress')),
                        number_format_i18n($table->getTotal())
                    );
                ?></span>
                <span class="pagination-links"><?php
                    echo paginate_links([
                        'base' => esc_url_raw(querystring(get_current_page(), ['paged' => '%#%'])),
                        'format' => '',
                        'prev_text' => '&lsaquo;',
                        'next_text' => '&rsaquo;',
                        'total' => $table->totalPages(),
                        'current' => $table->currentPage(),
                    ]);
                ?></span>
            </div>
        </div>
    <?php } ?>
</div>

<script>
    function copyToClipboard(newClip) {
        navigator.clipboard.writeText(newClip).then(
            () => {
                return null;
            },
            () => {
                alert("An error occurred while trying to copy to clipboard")
            },
        );
    }
</script>