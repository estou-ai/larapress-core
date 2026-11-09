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
        <th scope="col" id="<?= esc_attr($column->getName()) ?>" class="<?= esc_attr($class) ?>"<?php
            if ($column->getWidth()) {
                echo ' style="width:' . esc_attr($column->getWidth()) . '"';
            }
        ?>>
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

<?php
// A caller that prints its own heading and wrapper before rendering a table
// would otherwise get a second <h1>; renderTable() sets $standalone = false
// for that case.
$standalone = !isset($standalone) || $standalone;
?>
<?php if ($standalone) { ?>
<div class="wrap">
    <h1><?= esc_html(get_admin_page_title()) ?></h1>
<?php } ?>

    <?php if (isset($table) && $table->hasFilters()) { ?>
        <form method="get" class="flad-table-filters tablenav top">
            <?php // Carries the admin page through, or submitting leaves it. ?>
            <input type="hidden" name="page" value="<?= esc_attr(sanitize_key($_GET['page'] ?? '')) ?>">

            <?php foreach ($table->getKeptParams() as $keepKey => $keepValue) { ?>
                <input type="hidden" name="<?= esc_attr($keepKey) ?>" value="<?= esc_attr($keepValue) ?>">
            <?php } ?>

            <?php foreach ($table->getFilters() as $key => $filter) {
                $value = Larapress\Components\Table\Table::filterValue($key);
                $id = 'filter-' . sanitize_key($key);
                ?>
                <label class="screen-reader-text" for="<?= esc_attr($id) ?>">
                    <?= esc_html($filter['label'] ?? $key) ?>
                </label>

                <?php if (($filter['type'] ?? 'select') === 'search') { ?>
                    <input type="search" id="<?= esc_attr($id) ?>" name="<?= esc_attr($key) ?>"
                           value="<?= esc_attr($value) ?>"
                           placeholder="<?= esc_attr($filter['label'] ?? '') ?>">
                <?php } else { ?>
                    <select id="<?= esc_attr($id) ?>" name="<?= esc_attr($key) ?>">
                        <option value=""><?= esc_html($filter['all'] ?? $filter['label'] ?? '') ?></option>
                        <?php foreach (($filter['options'] ?? []) as $optValue => $optLabel) { ?>
                            <option value="<?= esc_attr($optValue) ?>"<?= selected($value, (string) $optValue, false) ?>>
                                <?= esc_html($optLabel) ?>
                            </option>
                        <?php } ?>
                    </select>
                <?php } ?>
            <?php } ?>

            <button type="submit" class="button"><?= esc_html__('Filtrar', 'larapress') ?></button>

            <?php if (array_filter(array_map(
                fn($k) => Larapress\Components\Table\Table::filterValue($k),
                array_keys($table->getFilters())
            ))) { ?>
                <a class="button-link" href="<?= esc_url(add_query_arg(
                    array_merge(
                        ['page' => sanitize_key($_GET['page'] ?? '')],
                        $table->getKeptParams()
                    ),
                    admin_url('admin.php')
                )) ?>"><?= esc_html__('Limpar', 'larapress') ?></a>
            <?php } ?>
        </form>
    <?php } ?>

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
        <tr>
            <?php renderColumns($columns) ?>
            <?php if (isset($table) && $table->hasActions()) { ?>
                <th scope="col" class="manage-column column-actions"><?= esc_html(
                    $table->actionsLabel()
                ) ?></th>
            <?php } ?>
        </tr>
        </thead>

        <tbody id="the-list">
        <?php if (!$data) { ?>
            <tr class="no-items">
                <td class="colspanchange" colspan="<?= count($columns) + (isset($table) && $table->hasActions() ? 1 : 0) ?>">
                    <?= esc_html($emptyMessage ?? __('No entries found.', 'larapress')) ?>
                </td>
            </tr>
        <?php } ?>
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
                <?php if (isset($table) && $table->hasActions()) { ?>
                    <td class="column-actions">
                        <?php // Separated, so "Editar Inscrições Eliminar" does not read as one word. ?>
                        <span class="row-actions visible"><?php
                            $links = $table->actionsFor($entry);
                            $last = count($links) - 1;

                            foreach ($links as $i => $link) {
                                printf(
                                    '<span><a href="%s">%s</a>%s</span>',
                                    esc_url($link['url']),
                                    esc_html($link['label']),
                                    $i < $last ? ' | ' : ''
                                );
                            }
                        ?></span>
                    </td>
                <?php } ?>
            </tr>
        <?php } ?>
        </tbody>

        <?php // A repeated header only helps once the rows outrun the screen. ?>
        <?php if (count($data) > 20) { ?>
        <tfoot>
        <tr>
            <?php renderColumns($columns) ?>
            <?php if (isset($table) && $table->hasActions()) { ?>
                <th scope="col" class="manage-column column-actions"><?= esc_html(
                    $table->actionsLabel()
                ) ?></th>
            <?php } ?>
        </tr>
        </tfoot>
        <?php } ?>

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
<?php if ($standalone) { ?>
</div>
<?php } ?>

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