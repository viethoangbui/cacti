<?php
require_once('./include/auth.php');
require_once('./include/myCommond.php');

if (get_request_var('action') === 'ajax_delete') {
    $id = get_filter_request_var('delete_id');

    $check = db_fetch_cell_prepared(
        'SELECT count(*)
        FROM model
        WHERE device_type_id = ? and is_enable = 1',
        array($id)
    );

    if ($check == 0) {
        $isEnable = db_fetch_cell_prepared('SELECT is_enable FROM device_type WHERE id = ?', array($id));
        $enableInsert = $isEnable == "1" ? "0" : "1";

        db_execute_prepared(
            'UPDATE device_type
            SET is_enable = ?, updated_at = CURRENT_TIMESTAMP()
            WHERE id = ?',
            array($enableInsert, $id)
        );
        echo json_encode(['message' => 'success']);
    } else {
        echo json_encode(['message' => 'failed']);
    }

    return;
}

if (get_request_var('action') === 'clear' || !isset($_GET['page'])) {
    unset($_SESSION['common_sort_column'], $_SESSION['common_sort_direction']);
}

top_header();
initial();
?>
<link rel="stylesheet" href="./include/css/deviceType.css" />
<?php

function drawSelect()
{
    $sql = 'SELECT id, name FROM suppliers WHERE is_enable = 1';

    $data = db_fetch_assoc($sql);
    $selector = '
                    <select name="supplier_id" id="supplier_select" required>
                    <option>None</option>
                    ';
    $content = '';
    foreach ($data as $value) {
        $content .= "<option value=\"{$value['id']}\">{$value['name']}</option>";
    }

    $selector .= $content;
    $selector .= '</select>';

    return $selector;
}

function update($input, $supplierId, $id)
{
    $pattern = '/<[^>]*>|javascript:[^"\'\s]*|data:[^"\'\s]*|on\w+\s*=\s*[^"\'\s]*/i';

    $name = preg_replace($pattern, '', $input);

    $nameTrim = trim(
        trim(htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), ' ')
    );

    if (!$nameTrim) {
        return;
    }

    $names = db_fetch_cell_prepared(
        'SELECT count(*)
        FROM device_type
        WHERE id != ? and name = ? and is_enable = 1',
        array($id, $nameTrim)
    );

    if ($names == 0) {
        $supplierId = trim(htmlspecialchars($supplierId, ENT_QUOTES, 'UTF-8'), ' ');

        db_execute_prepared(
            'UPDATE device_type
                SET name = ?, 
                updated_at = CURRENT_TIMESTAMP(),
                supplier_id = ?,
                edited_by = ?
                WHERE id = ?',
            array($nameTrim, $supplierId, $_SESSION['sess_user_id'], $id)
        );
    } else {
        raise_message('name_used');
    }
}

function create($input, $supplierId)
{
    $pattern = '/<[^>]*>|javascript:[^"\'\s]*|data:[^"\'\s]*|on\w+\s*=\s*[^"\'\s]*/i';

    $name = preg_replace($pattern, '', $input);

    $nameTrim = trim(
        trim(htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), ' ')
    );

    if (!$nameTrim) {
        return;
    }

    $supplierId = trim(htmlspecialchars($supplierId, ENT_QUOTES, 'UTF-8'), ' ');

    $total = db_fetch_cell_prepared(
        'SELECT COUNT(*)
            FROM device_type
            WHERE name = ?
            and
            supplier_id = ? and is_enable = 1',
        array($nameTrim, $supplierId)
    );

    if ($total >= 1) {
        raise_message('name_used');
    } else {
        db_execute_prepared(
            'INSERT INTO device_type
                (name, supplier_id, owner, edited_by) VALUES (?, ?, ?, ?)',
            array($nameTrim, $supplierId, $_SESSION['sess_user_id'], $_SESSION['sess_user_id'])
        );
    }
}

function initial()
{
    if (isset($_POST['name']) && isset($_POST['supplier_id'])) {
        $action = !empty($_POST['id']) ? 'update' : 'create';

        switch ($action) {
            case 'update':
                update($_POST['name'], $_POST['supplier_id'], $_POST['id']);
                break;
            case 'create':
                create($_POST['name'], $_POST['supplier_id']);
                break;
            default:
                break;
        }
    }
}

html_start_box(__('Device type'), '100%', '', '3', 'center', [
    [
        'href' => '#ex1dt',
        'rel' => 'modal:open'
    ]
]);
?>
<tr class='even'>
    <td>
        <form id='form_device_type' action=''>
            <table class='filterTable'>
                <tr>
                    <td>
                        <?php print __('Search'); ?>
                    </td>
                    <td>
                        <input type='text' class='ui-state-default ui-corner-all' id='filter' size='25' value='<?php print html_escape_request_var('filter'); ?>'>
                    </td>
                    <td>
                        <span>
                            <input type='button' class='ui-button ui-corner-all ui-widget' id='refresh' value='<?php print __esc('Go'); ?>' title='<?php print __esc('Set/Refresh Filters'); ?>'>
                            <input type='button' class='ui-button ui-corner-all ui-widget' id='clear' value='<?php print __esc('Clear'); ?>' title='<?php print __esc('Clear Filters'); ?>'>
                        </span>
                    </td>
                </tr>
            </table>
        </form>
        <script type='text/javascript'>
            function applyFilter() {
                strURL = 'device_type.php';
                strURL += '?filter=' + $('#filter').val();
                strURL += '&header=false&nostate=true';
                loadPageNoHeader(strURL);
            }

            function clearFilter() {
                strURL = 'device_type.php?action=clear&header=false&nostate=true';
                loadPageNoHeader(strURL);
            }

            $(function() {
                $('#refresh').click(function() {
                    applyFilter();
                });

                $('#clear').click(function() {
                    clearFilter();
                });

                $('#form_device_type').submit(function(event) {
                    event.preventDefault();
                    applyFilter();
                });
            });
        </script>
    </td>
</tr>

<?php
$display_text = array(
    'nosort1' => array(
        'display' => __('#'),
        'align' => 'left',
        'tip' => __('Order')
    ),
    'name' => array(
        'display' => __('Device type'),
        'align' => 'left',
        'sort' => 'ASC',
        'tip' => __('Device type.')
    ),
    'supplier_id' => array(
        'display' => __('Supplier'),
        'align' => 'left',
        'sort' => 'ASC',
        'tip' => __('Supplier Name.')
    ),
    'owner' => array(
        'display' => __('Owner'),
        'align' => 'left',
        'sort' => 'ASC',
        'tip' => __('Owner.')
    ),
    'updated_at' => array(
        'display' => __('Last Edited'),
        'align' => 'left',
        'sort' => 'ASC',
        'tip' => __('Owner.')
    ),
    'edited_by' => array(
        'display' => __('Edited By'),
        'align' => 'left',
        'sort' => 'ASC'
    ),
    'nosort3' => array(
        'display' => __('Action'),
        'align' => 'left',
        'tip' => __('Actions.')
    ),
);
$display_text_size = sizeof($display_text);
$display_text = api_plugin_hook_function('device_type_display_text', $display_text);
$limit = 15;
$pageDefault = 1;
$page = isset($_GET['page']) ? convertStrPreventXss($_GET['page']) : $pageDefault;
$page = (int)$page !== 0 ? (int)$page : $pageDefault;

$offset = ($page - 1) * $limit;
$sql = "SELECT device_type.*, 
        ua1.username AS edited_name_by,
        ua2.username AS owner_name,
        sp.name AS supplier_name
        FROM device_type 
        LEFT JOIN user_auth AS ua1 ON device_type.edited_by = ua1.id
        LEFT JOIN user_auth AS ua2 ON device_type.owner = ua2.id
        LEFT JOIN suppliers AS sp ON device_type.supplier_id = sp.id
        WHERE device_type.is_enable = 1";

$searchFilter = !empty($_GET['filter']) ? '%' . html_escape_request_var('filter') . '%' : null;

$sortColumn = !empty($_GET['sort_column']) ? $_GET['sort_column'] : $_SESSION['common_sort_column'] ?? null;
$sortDirection = !empty($_GET['sort_direction']) ? $_GET['sort_direction'] : $_SESSION['common_sort_direction'] ?? null;

if ($sortColumn) $_SESSION['common_sort_column'] = $sortColumn;
if ($sortDirection) $_SESSION['common_sort_direction'] = $sortDirection;

if ($searchFilter) {
    $sql .= " AND device_type.name LIKE ?";
}

if ($sortColumn && $sortDirection) {
    $sql .= " ORDER BY device_type.$sortColumn $sortDirection";
}
$sql .= " LIMIT $offset, $limit";

$deviceTypes = $searchFilter ? db_fetch_assoc_prepared($sql, array($searchFilter)) : db_fetch_assoc($sql);
$total = !$searchFilter ? db_fetch_cell("SELECT COUNT(*) FROM device_type WHERE is_enable = 1")
    : db_fetch_cell_prepared("SELECT COUNT(*) FROM device_type WHERE is_enable = 1 AND name LIKE ?", [$searchFilter]);
$pageCount = ceil($total / $limit);
form_start('device_type.php', 'chk');
?>
<?php

html_start_box('', '100%', '', '3', 'center', '');
html_header_sort($display_text, $sortColumn, $sortDirection, false);

if (sizeof($display_text) != $display_text_size && cacti_sizeof($deviceTypes)) { //display_text changed
    api_plugin_hook_function('supplier_table_replace', $deviceTypes);
} else if (cacti_sizeof($deviceTypes)) {
    foreach ($deviceTypes as $index => $value) {
        form_alternate_row($value['id'], true);
        form_selectable_cell(filter_value((($page - 1) * $limit + 1) + $index, get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value($value['name'], get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value($value['supplier_name'], get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value($value['owner_name'], get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value($value['updated_at'], get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value($value['edited_name_by'], get_request_var('filter')), $value['id']);
        echo "<td class=\"nowrap\" style=\"display:none;\">{$value['supplier_id']}</td>";
        echo "<td class=\"nowrap\">
                                <a class=\"device_type-edit\" 
                                    style=\"cursor:pointer;\"
                                    href=\"#ex1dt\" rel=\"modal:open\"
                                    >Edit</a>
                                <a class=\"device_type-delete\" 
                                    style=\"cursor:pointer;\"
                                    href=\"#ex2\" rel=\"modal:open\"
                                >Delete</a>
                            </td>";

        form_end_row();
    }
} else {
    print "<tr class='tableRow'><td colspan='" . (cacti_sizeof($display_text) + 1) . "'><em>" . __('No Suppliers Found') . "</em></td></tr>";
}

html_end_box(false);

form_end();
api_plugin_hook('device_table_bottom');
$showDots = false;

?>
<?php if ($pageCount > 1): ?>
    <div class="navBarNavigation" style="margin:12px 0;">
        <div class="navBarNavigationCenter">
            <?= (($page - 1) * $limit) + 1 ?> to <?= (($page - 1) * $limit) + count($deviceTypes) ?> of <?= $total ?>
            [ <ul class="pagination">
                <?php for ($i = 0; $i < $pageCount; $i++): ?>
                    <?php if ($i == 0 || $i + 1 == $pageCount || ($page < $i + 4 && $page > $i - 3)): ?>
                        <li>
                            <a url="?page=<?= $i + 1 ?>" class="<?= $page === ($i + 1) ? 'active' : '' ?>"
                                style="cursor: pointer;">
                                <?= $i + 1 ?></a>
                        </li>
                    <?php else: ?>
                        <?php if (!$showDots || $page == $i - 4):
                            $showDots = true; ?>
                            <li><span>..</a></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endfor; ?>
            </ul> ]
        </div>
    </div>
    <script>
        $(function() {
            $('ul.pagination li a').on('click', (event) => {
                $('#ex1dt').remove()
                $('#ex2').remove()

                strURL = 'device_type.php' + $(event.target).attr('url') + '&header=false&nostate=true';
                loadPageNoHeader(strURL);
            })
        })
    </script>
<?php endif; ?>

<section>
    <div id="ex1dt" class="modal">
        <div class="modal-content">
            <h2 style="margin-top: 0px;">Device type Create</h2>
            <div class="modal-body">
                <form action="" method="post" id="device_type-modal">
                    <div class="form-container-type">
                        <div class="area-frist area-select mb-1">
                            <label class="col-form-label">Supplier:</label>
                            <?= drawSelect() ?>
                        </div>

                        <div class="area-frist">
                            <label for="device_type-name" class="col-form-label">Name:</label>
                            <input type="text"
                                class="form-control"
                                id="device_type-name"
                                name="name"
                                placeholder="Enter name">
                            <input type="hidden" id="device_type-id" name="id" />
                        </div>
                    </div>
                    <div class="area-second">
                        <a href="#" rel="modal:close">
                            <button type="button">Close</button>
                        </a>
                        <button type="submit">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<section>
    <div id="ex2" class="modal">
        <div class="modal-content">
            <div class="modal-body">
                <p>Are you sure about action?</p>
                <div>
                    <a href="#" rel="modal:close">
                        <button>Close</button>
                    </a>
                    <button
                        type="button"
                        class="btn btn-danger">
                        Delete</button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    $(document).ready(function() {
        document.querySelectorAll('.device_type-edit').forEach(element => {
            element.addEventListener('click', (el) => {
                const tr = el.target.parentNode.parentNode;
                $('#device_type-name').val(tr.children[1].textContent);
                $('#device_type-id').val(tr.getAttribute('id'));
                $('#supplier_id-update').attr('supplier_id-update', tr.children[6].textContent);
                $('#supplier_select').val(tr.children[6].textContent);

                $('#supplier_select-button .ui-selectmenu-text').text(tr.children[2].textContent);
            })
        });

        document.querySelectorAll('.device_type-delete').forEach(element => {
            element.addEventListener('click', (el) => {
                const tr = el.target;
                const id = tr.parentNode.parentNode.getAttribute('id');
                localStorage.setItem('delete_id', id);
            })
        });

        $('#ex1dt').on('modal:close', () => {
            $('device_type-name').val('');
            $('#supplier_select').val('None')
            $('#supplier_select-button .ui-selectmenu-text').text('None')

            $('#supplier_select-button').css(
                'border', ''
            )

            $('#device_type-name').css(
                'border', ''
            )
        })

        $('#ex1dt').on('modal:open', () => {
            const isUpdate = document.getElementById('device_type-name').value;
            if (!isUpdate) {
                $('.modal-content h2').text('Device type Create')
            } else {
                $('.modal-content h2').text('Device type Update')
            }
        })

        $('#ex1dt .area-second a').click(() => {
            document.getElementById('device_type-name').value = '';
        })

        $('.btn-danger').on('click', () => {
            const deleteId = localStorage.getItem('delete_id', id);
            if (deleteId) {
                renderLoading()
                $.post('device_type.php?action=ajax_delete', {
                    delete_id: deleteId,
                    __csrf_magic: csrfMagicToken
                }).done(function(data) {
                    const responseData = JSON.parse(data)
                    Pace.stop()

                    if (responseData.message === 'failed') {
                        sessionMessage = {
                            message: '<?php print __esc('Bản ghi này đã được sử dụng!'); ?>',
                            level: MESSAGE_LEVEL_ERROR
                        };
                        displayMessages()
                    } else {
                        let page = '<?= $page ?>';
                        if ('<?= count($deviceTypes) ?>' == 1 && page != 1) {
                            page = Number(page) - 1
                        }

                        let loadPageUrl = `device_type.php?page=${page}&header=false&nostate=true`
                        loadPageNoHeader(loadPageUrl)
                        applySkin()

                        $('.jquery-modal').hide()
                        $('#ex1dt').remove()
                        $('#ex2').remove()
                    }
                });
            }
        })

        $('#device_type-modal').submit((e) => {
            e.preventDefault();
            const formData = new FormData(e.target);

            const dataSend = {};
            formData.forEach(function(value, key) {
                dataSend[key] = value;
            });

            $('.area-second button[type=submit]').removeAttr('disabled');
            $('.area-second button[type=submit]').removeClass('ui-button-disabled ui-state-disabled');
            let supplierSelectValue = $('#supplier_select').val();
            let inputTextValue = $('#device_type-name').val();
            let isValidate = supplierSelectValue === 'None' || inputTextValue === ''

            if (isValidate) {
                if (supplierSelectValue === 'None') {
                    $('#supplier_select-button').css(
                        'border', '1px solid red'
                    )
                }

                if (inputTextValue === '') {
                    $('#device_type-name').css(
                        'border', '1px solid red'
                    )
                }

                sessionMessage = {
                    message: '<?php print __esc('Bạn cần điền đầy đủ các trường'); ?>',
                    level: MESSAGE_LEVEL_ERROR
                };
                displayMessages()
                return;
            }

            let page = '<?= ceil(($total + 1) / $limit) ?>'
            if (dataSend?.id) {
                page = '<?= $page ?>'
            }

            let loadPageUrl = `device_type.php?page=${page}&header=false&nostate=true`
            renderLoading()

            $.post('device_type.php', dataSend, function(data) {
                Pace.stop()
                loadPageNoHeader(loadPageUrl)
                applySkin()

                $('.jquery-modal').hide()
                $('#ex1dt').remove()
                $('#ex2').remove()
            });
        })

        $('#supplier_select').change((e) => {
            if (e.target.value !== 'None') {
                $('#supplier_select-button').css({
                    'border': '1px solid #d3d3d3'
                })
            }
        })

        $('#device_type-name').on('change keydown', (e) => {
            if (e.target.value !== '') {
                $('#device_type-name').css({
                    'border': '1px solid #d3d3d3'
                })
            }
        })
    })
</script>

<?php
html_end_box();
bottom_footer();
?>