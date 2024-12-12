<?php
require_once('./include/auth.php');
require_once('./include/myCommond.php');

if (get_request_var('action') === 'ajax_device_type') {
    $noneItem = ['id' => 'None', 'name' => 'None'];
    if (isempty_request_var('supplier_id')) {
        echo json_encode([$noneItem]);
    } else {
        $supplierId = get_filter_request_var('supplier_id');
        $models = db_fetch_assoc_prepared(
            'SELECT name, id
				FROM device_type
				WHERE supplier_id = ?
				AND is_enable = 1
				',
            array($supplierId)
        );
        array_unshift($models, $noneItem);
        echo json_encode($models);
    }
    return;
}

if (get_request_var('action') === 'ajax_model') {
    $model = db_fetch_row_prepared('SELECT device_type_id, snmp_version FROM model WHERE id = ?', array($_GET['id']));

    $deviceType = db_fetch_row_prepared(
        'SELECT name, id, supplier_id,name
            FROM device_type
            WHERE id = ?
            AND is_enable = 1
            ',
        array($model['device_type_id'])
    );

    $supplier = db_fetch_row_prepared(
        'SELECT id,name
            FROM suppliers
            WHERE id = ?
            AND is_enable = 1
            ',
        array($deviceType['supplier_id'])
    );

    $models = db_fetch_assoc_prepared(
        'SELECT name, id
            FROM device_type
            WHERE supplier_id = ?
            AND is_enable = 1
            ',
        array($supplier['id'])
    );
    $noneItem = ['id' => 'None', 'name' => 'None'];
    array_unshift($models, $noneItem);

    echo json_encode([
        'device_type' => $deviceType,
        'supplier' => $supplier,
        'device_types' => $models,
        'model' => $model
    ]);

    return;
}

if (get_request_var('action') === 'ajax_delete') {
    $id = get_filter_request_var('delete_id');

    $check = db_fetch_cell_prepared(
        'SELECT count(*)
        FROM host
        WHERE model = ?',
        array($id)
    );

    if ($check == 0) {
        $isEnable = db_fetch_cell_prepared('SELECT is_enable FROM model WHERE id = ?', array($id));
        $enableInsert = $isEnable == "1" ? "0" : "1";

        db_execute_prepared(
            'UPDATE model
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
<link rel="stylesheet" href="./include/css/model.css" />
<?php

function drawBodyTable()
{
    $sql = 'SELECT m.*,dt.name as device_type_name, sup.name AS supplier_name FROM model AS m
                LEFT JOIN device_type as dt
                ON m.device_type_id=dt.id
                LEFT JOIN suppliers as sup
                ON dt.supplier_id=sup.id
                WHERE m.is_enable = 1';

    if (!empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $sql .= " AND m.name LIKE ?";
        $data = db_fetch_assoc_prepared($sql, array($search));
    } else {
        $data = db_fetch_assoc($sql);
    }

    if (count($data) === 0) {
        return "<tr class='tableRow'><td colspan='" . (3) . "'><em>" . __('No Model type Found') . "</em></td></tr>\n";
    }

    $result = '';

    foreach ($data as $index => $value) {
        $newIndex = $index + 1;
        $result .= "
                        <tr class=\"odd selectable tableRow\" 
                            id=\"{$value["id"]}\"
                            name=\"{$value["name"]}\" 
                            device-type=\"{$value["device_type_id"]}\"
                            device_type-name=\"{$value["device_type_name"]}\" 
                            >
                            <td class=\"nowrap\">
                                {$newIndex}
                            </td>
                            <td class=\"nowrap model-name\">
                                {$value["name"]}
                            </td>
                            <td class=\"nowrap\">
                                {$value["snmp_version"]}
                            </td>
                            <td class=\"nowrap\">
                                {$value["supplier_name"]}
                            </td>
                            <td class=\"nowrap\">
                                {$value["device_type_name"]}
                            </td>
                            <td class=\"nowrap\">
                                {$value["owner"]}
                            </td>
                            <td class=\"nowrap\">
                                {$value["updated_at"]}
                            </td>
                            <td class=\"nowrap\">
                                {$value["edited_by"]}
                            </td>
                            <td class=\"nowrap\">
                                <a class=\"model-edit\" 
                                    style=\"cursor:pointer;\"
                                    href=\"#ex1m\" rel=\"modal:open\"
                                    >Edit</a>
                                <a class=\"model-delete\" 
                                    style=\"cursor:pointer;\"
                                    href=\"#ex2\" rel=\"modal:open\"
                                >Delete</a>
                            </td>
                        </tr>
                        ";
    }

    return $result;
}

function drawSelectSuppliers()
{
    $sql = 'SELECT id, name FROM suppliers WHERE is_enable = 1';

    $data = db_fetch_assoc($sql);
    $selector = '
                    <select name="supplier_id" id="supplier_select">
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

function update($input, $device_typeId, $snmpVersion = '', $id)
{
    $pattern = '/<[^>]*>|javascript:[^"\'\s]*|data:[^"\'\s]*|on\w+\s*=\s*[^"\'\s]*/i';

    $name = preg_replace($pattern, '', $input);

    $nameTrim = trim(
        trim(htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), ' ')
    );

    if (!$nameTrim) {
        return;
    }

    $device_typeId = trim(htmlspecialchars($device_typeId, ENT_QUOTES, 'UTF-8'), ' ');

    $names = db_fetch_cell_prepared(
        'SELECT count(*)
        FROM model
        WHERE id != ? 
        and name = ? 
        and is_enable = 1
        and device_type_id = ?
        ',
        array($id, $nameTrim, $device_typeId)
    );

    if ($names == 0) {
        $username = db_fetch_cell_prepared(
            'SELECT username
        FROM user_auth
        WHERE id = ?',
            array($_SESSION['sess_user_id'])
        );

        db_execute_prepared(
            'UPDATE model
                SET name = ?, 
                updated_at = CURRENT_TIMESTAMP(),
                device_type_id = ?,
                snmp_version = ?,
                edited_by = ?
                WHERE id = ?',
            array(
                $nameTrim,
                $device_typeId,
                trim(
                    htmlspecialchars($snmpVersion, ENT_QUOTES, 'UTF-8'),
                    ' '
                ),
                $username,
                $id
            )
        );
        header("Location: " . $_SERVER['REQUEST_URI']);
    } else {
        raise_message('name_used');
    }
}

function create($input, $deviceTypeId, $snmpVersion = "")
{
    $pattern = '/<[^>]*>|javascript:[^"\'\s]*|data:[^"\'\s]*|on\w+\s*=\s*[^"\'\s]*/i';

    $name = preg_replace($pattern, '', $input);

    $nameTrim = trim(
        trim(htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), ' ')
    );

    if (!$nameTrim) {
        return;
    }

    $deviceTypeId = trim(htmlspecialchars($deviceTypeId, ENT_QUOTES, 'UTF-8'), ' ');

    $total = db_fetch_cell_prepared(
        'SELECT COUNT(*)
            FROM model
            WHERE name = ? 
            and device_type_id = ?
            and is_enable = 1',
        array($nameTrim, $deviceTypeId)
    );

    if ($total >= 1) {
        raise_message('name_used');
    } else {
        $user = db_fetch_assoc_prepared('SELECT username
                                            FROM user_auth 
                                            WHERE id = ?', array($_SESSION['sess_user_id']));
        db_execute_prepared(
            'INSERT INTO model
                (name, device_type_id, owner, edited_by, snmp_version) VALUES (?, ?, ?, ?, ?)',
            array($nameTrim, $deviceTypeId, $user[0]['username'], $user[0]['username'], trim(htmlspecialchars($snmpVersion, ENT_QUOTES, 'UTF-8'), ' '))
        );
        header("Location: " . $_SERVER['REQUEST_URI']);
    }
}

function delete($id)
{
    $check = db_fetch_cell_prepared(
        'SELECT count(*)
        FROM host
        WHERE model = ?',
        array($id)
    );

    if ($check == 0) {
        $isEnable = db_fetch_cell_prepared('SELECT is_enable FROM model WHERE id = ?', array($id));
        $enableInsert = $isEnable == "1" ? "0" : "1";

        db_execute_prepared(
            'UPDATE model
                SET is_enable = ?, updated_at = CURRENT_TIMESTAMP()
                WHERE id = ?',
            array($enableInsert, $id)
        );
        return 1;
    } else {
        messageBox('Bản ghi đang được sử dụng!');
        return 0;
    }
}

function initial()
{
    if (isset($_POST['name']) && isset($_POST['device_type_id'])) {
        $action = !empty($_POST['id']) ? 'update' : 'create';
        switch ($action) {
            case 'update':
                update($_POST['name'], $_POST['device_type_id'], $_POST['snmp_version'], $_POST['id']);
                break;
            case 'create':
                create($_POST['name'], $_POST['device_type_id'], $_POST['snmp_version']);
                break;
            default:
                break;
        }
    }
}

html_start_box(__('Model'), '100%', '', '3', 'center', [
    [
        'href' => '#ex1m',
        'rel' => 'modal:open'
    ]
]);
?>
<tr class='even'>
    <td>
        <form id='form_model' action='sites.php'>
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
                strURL = 'model.php';
                strURL += '?filter=' + $('#filter').val();
                strURL += '&header=false&nostate=true';
                loadPageNoHeader(strURL);
            }

            function clearFilter() {
                strURL = 'model.php?action=clear&header=false&nostate=true';
                loadPageNoHeader(strURL);
            }

            $(function() {
                $('#refresh').click(function() {
                    applyFilter();
                });

                $('#clear').click(function() {
                    clearFilter();
                });

                $('#form_model').submit(function(event) {
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
        'display' => __('Model'),
        'align' => 'left',
        'sort' => 'ASC',
        'tip' => __('Model.')
    ),
    'snmp_version' => array(
        'display' => __('Snmp version'),
        'align' => 'left',
        'sort' => 'ASC',
        'tip' => __('Snmp version.')
    ),
    'supplier_id' => array(
        'display' => __('Supplier'),
        'align' => 'left',
        'sort' => 'ASC',
        'tip' => __('Supplier.')
    ),
    'device_type_id' => array(
        'display' => __('Device type'),
        'align' => 'left',
        'sort' => 'ASC',
        'tip' => __('Device type.')
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
        'tip' => __('Updated at.')
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
$sql = "SELECT m.*, 
        ua1.username AS edited_name_by,
        ua2.username AS owner_name,
        sup.name AS supplier_name,
        dt.name AS device_type_name
        FROM model AS m
        LEFT JOIN user_auth AS ua1 ON m.edited_by = ua1.id
        LEFT JOIN user_auth AS ua2 ON m.owner = ua2.id
        LEFT JOIN device_type as dt
            ON m.device_type_id=dt.id
            LEFT JOIN suppliers as sup
            ON dt.supplier_id=sup.id
        WHERE m.is_enable = 1";

$searchFilter = !empty($_GET['filter']) ? '%' . html_escape_request_var('filter') . '%' : null;

$sortColumn = !empty($_GET['sort_column']) ? $_GET['sort_column'] : $_SESSION['common_sort_column'] ?? null;
$sortDirection = !empty($_GET['sort_direction']) ? $_GET['sort_direction'] : $_SESSION['common_sort_direction'] ?? null;

if ($sortColumn) $_SESSION['common_sort_column'] = $sortColumn;
if ($sortDirection) $_SESSION['common_sort_direction'] = $sortDirection;

if ($searchFilter) {
    $sql .= " AND model.name LIKE ?";
}

if ($sortColumn && $sortDirection) {
    $sql .= " ORDER BY m.$sortColumn $sortDirection";
}
$sql .= " LIMIT $offset, $limit";
$models = $searchFilter ? db_fetch_assoc_prepared($sql, array($searchFilter)) : db_fetch_assoc($sql);
$total = !$searchFilter ? db_fetch_cell("SELECT COUNT(*) FROM model WHERE is_enable = 1")
    : db_fetch_cell_prepared("SELECT COUNT(*) FROM model WHERE is_enable = 1 AND name LIKE ?", [$searchFilter]);
$pageCount = ceil($total / $limit);
form_start('model.php', 'chk');
?>
<?php

html_start_box('', '100%', '', '3', 'center', '');
html_header_sort($display_text, $sortColumn, $sortDirection, false);

if (sizeof($display_text) != $display_text_size && cacti_sizeof($models)) { //display_text changed
    api_plugin_hook_function('model_table_replace', $models);
} else if (cacti_sizeof($models)) {
    foreach ($models as $index => $value) {
        form_alternate_row($value['id'], true);
        form_selectable_cell(filter_value((($page - 1) * $limit + 1) + $index, get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value($value['name'], get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value($value['snmp_version'], get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value($value['supplier_name'], get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value($value['device_type_name'], get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value($value['owner_name'], get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value($value['updated_at'], get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value($value['edited_name_by'], get_request_var('filter')), $value['id']);
        // echo "<td class=\"nowrap\" style=\"display:none;\">{$value['supplier_id']}</td>";
        echo "<td class=\"nowrap\">
                                <a class=\"model-edit\" 
                                    style=\"cursor:pointer;\"
                                    href=\"#ex1m\" rel=\"modal:open\"
                                    >Edit</a>
                                <a class=\"model-delete\" 
                                    style=\"cursor:pointer;\"
                                    href=\"#ex2\" rel=\"modal:open\"
                                >Delete</a>
                            </td>";

        form_end_row();
    }
} else {
    print "<tr class='tableRow'><td colspan='" . (cacti_sizeof($display_text) + 1) . "'><em>" . __('No Models Found') . "</em></td></tr>";
}

html_end_box(false);

form_end();
api_plugin_hook('device_table_bottom');
$showDots = false;

?>
<?php if ($pageCount > 1): ?>
    <div class="navBarNavigation" style="margin:12px 0;">
        <div class="navBarNavigationCenter">
            <?= (($page - 1) * $limit) + 1 ?> to <?= (($page - 1) * $limit) + count($models) ?> of <?= $total ?>
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
    <div id="ex1m" class="modal">
        <div class="modal-content">
            <h2 style="margin-top: 0px;text-align:center;">Model Create</h2>
            <div class="modal-body">
                <form action="" method="post" id="model-modal">
                    <div class="area-frist area-select mb-1" id="model-form-first-area">
                        <label class="col-form-label">Supplier:</label>
                        <?= drawSelectSuppliers() ?>
                    </div>

                    <div class="area-frist area-select mb-1">
                        <label class="col-form-label">Device type:</label>
                        <select id="device_type_id" name="device_type_id" class="ui-selectmenu-button ui-button ui-widget ui-selectmenu-button-open ui-corner-top">
                            <option value="None">None</option>
                        </select>
                    </div>

                    <div class="area-frist mb-1">
                        <label for="model-name" class="col-form-label">Model name:</label>
                        <input type="text"
                            class="form-control"
                            id="model-name"
                            name="name"
                            placeholder="Enter name">
                        <input type="hidden" id="model-id" name="id" />
                    </div>

                    <div class="area-frist mb-1" id="model-snmp-ver">
                        <label for="snmp_version" class="col-form-label">Snmp version:</label>
                        <input type="text"
                            class="form-control"
                            id="snmp_version"
                            name="snmp_version"
                            placeholder="Enter snmp version">
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

<script src="./include/js/host-custom.js"></script>
<script>
    $(document).ready(function() {
        $('#ex1m').on('modal:close', () => {
            document.getElementById('model-name').value = '';
            $('#supplier_select').val('None')
            $('#supplier_select-button .ui-selectmenu-text').text('None')

            $('#device_type_id').val('None')
        })

        $('#ex1m').on('modal:open', () => {
            const isUpdate = document.getElementById('model-name').value
            if (!isUpdate) {
                $('.modal-content h2').text('Model Create')
            } else {
                $('.modal-content h2').text('Model Update')
            }

        })

        $('#ex1m .area-second a').click(() => {
            document.getElementById('model-name').value = '';
        })

        document.querySelectorAll('.model-edit').forEach(element => {
            element.addEventListener('click', (el) => {
                const tr = el.target.parentNode.parentNode;
                document.getElementById('model-name').value = tr.getAttribute('name');
                document.getElementById('model-id').value = tr.getAttribute('id');

                $('#device_type_id-update').attr('device_type_id-update', tr.getAttribute('device_type'));
                $('#device_type_select').val(tr.getAttribute('device_type'));
                $('#device_type_select-button .ui-selectmenu-text').text(tr.getAttribute('device_type-name'));

                $.get(`${urlPath}model.php?action=ajax_model&id=${tr.getAttribute('id')}`, (data, status) => {
                    if (status === 'success') {
                        const dataResponse = JSON.parse(data)

                        $('#supplier_select').val(dataResponse?.supplier.id)
                        $('#supplier_select-button .ui-selectmenu-text').text(dataResponse?.supplier?.name)
                        $('#snmp_version').val(dataResponse?.model?.snmp_version)

                        populateSelect('device_type_id', dataResponse.device_types, dataResponse?.device_type.id)
                    }
                })
            })
        });

        document.querySelectorAll('.model-delete').forEach(element => {
            element.addEventListener('click', (el) => {
                const tr = el.target;
                const id = tr.parentNode.parentNode.getAttribute('id');
                localStorage.setItem('delete_id', id);
            })
        });

        $('#supplier_select').on('change', (e) => {
            if (e.target.value === 'None') {
                $('#device_type_id').html('<option value="None">None</option>')
                return
            }

            getDeviceType(e);
        })

        $('#model-modal').submit((e) => {
            $('.area-second button[type=submit]').removeAttr('disabled');
            $('.area-second button[type=submit]').removeClass('ui-button-disabled ui-state-disabled');
            let supplierSelectValue = $('#supplier_select').val();
            let deviceTypeSelectValue = $('#device_type_id').val();
            let inputTextValue = $('#model-name').val();
            let inputTextSnmp = $('#snmp_version').val();
            let isValidate = supplierSelectValue === 'None' ||
                inputTextValue === '' ||
                deviceTypeSelectValue === 'None' ||
                inputTextSnmp === ''

            if (isValidate) {
                e.preventDefault();
                sessionMessage = {
                    message: '<?php print __esc('Bạn cần điền đầy đủ các trường'); ?>',
                    level: MESSAGE_LEVEL_ERROR
                };
                displayMessages()
            }

            if (supplierSelectValue === 'None') {
                $('#supplier_select-button').css({
                    'border': '1px solid red'
                })
            }

            if (deviceTypeSelectValue == 'None') {
                $('#device_type_id').css({
                    'border': '1px solid red'
                })
            }

            if (inputTextValue === '') {
                $('#model-name').css({
                    'border': '1px solid red'
                })
            }

            if (inputTextSnmp === '') {
                $('#snmp_version').css({
                    'border': '1px solid red'
                })
            }
        })

        $('#supplier_select').change((e) => {
            if (e.target.value !== 'None') {
                $('#supplier_select-button').css({
                    'border': '1px solid #d3d3d3'
                })
            }
        })

        $('#device_type_id').change((e) => {
            if (e.target.value !== '') {
                $('#device_type_id').css({
                    'border': '1px solid #d3d3d3'
                })
            }
        })

        $('#model-name').on('change keydown', (e) => {
            if (e.target.value !== '') {
                $('#model-name').css({
                    'border': '1px solid #d3d3d3'
                })
            }
        })

        $('#snmp_version').on('change keydown', (e) => {
            if (e.target.value !== '') {
                $('#snmp_version').css({
                    'border': '1px solid #d3d3d3'
                })
            }
        })

        $('.btn-danger').on('click', () => {
            const deleteId = localStorage.getItem('delete_id', id);

            if (deleteId) {
                $.post('model.php?action=ajax_delete', {
                    delete_id: deleteId,
                    __csrf_magic: csrfMagicToken
                }).done(function(data) {
                    const responseData = JSON.parse(data)

                    if (responseData.message === 'failed') {
                        sessionMessage = {
                            message: '<?php print __esc('Bản ghi này đã được sử dụng!'); ?>',
                            level: MESSAGE_LEVEL_ERROR
                        };
                        displayMessages()
                    } else {
                        location.reload()
                    }
                });
            }
        })
    })
</script>

<?php
html_end_box();
bottom_footer();
?>