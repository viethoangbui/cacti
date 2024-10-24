<?php
require_once('./include/auth.php');

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

top_header();
initial();
?>
<link rel="stylesheet" href="./include/css/deviceType.css" />
<?php
function drawBodyTable()
{
    $sql = 'SELECT dt.*, sup.name as supplier_name FROM device_type AS dt
                LEFT JOIN suppliers as sup
                ON dt.supplier_id=sup.id
                WHERE dt.is_enable = 1';

    if (!empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $sql .= " AND dt.name LIKE ?";
        $data = db_fetch_assoc_prepared($sql, array($search));
    } else {
        $data = db_fetch_assoc($sql);
    }

    if (count($data) === 0) {
        return "<tr class='tableRow'><td colspan='" . (3) . "'><em>" . __('No Device type Found') . "</em></td></tr>\n";
    }

    $result = '';

    foreach ($data as $index => $value) {
        $newIndex = $index + 1;
        $result .= "
                        <tr class=\"odd selectable tableRow\" 
                            id=\"{$value["id"]}\" 
                            name=\"{$value["name"]}\" 
                            supplier=\"{$value["supplier_id"]}\"
                            supplier-name=\"{$value["supplier_name"]}\"
                            >
                            <td class=\"nowrap\">
                                {$newIndex}
                            </td>
                            <td class=\"nowrap device_type-name\">
                                {$value["name"]}
                            </td>
                            <td class=\"nowrap\">
                                {$value["supplier_name"]}
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
                                <a class=\"device_type-edit\" 
                                    style=\"cursor:pointer;\"
                                    href=\"#ex1dt\" rel=\"modal:open\"
                                    >Edit</a>
                                <a class=\"device_type-delete\" 
                                    style=\"cursor:pointer;\"
                                    href=\"#ex2\" rel=\"modal:open\"
                                >Delete</a>
                            </td>
                        </tr>
                        ";
    }

    return $result;
}

function drawSelect()
{
    $sql = 'SELECT id, name FROM suppliers WHERE is_enable = 1';

    $data = db_fetch_assoc($sql);
    $selector = '
                    <select name="supplier_id" id="supplier_select" required>
                    <option>None</option>
                    ';
    $content = '';
    foreach ($data as $index => $value) {
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
        $username = db_fetch_cell_prepared(
            'SELECT username
        FROM user_auth
        WHERE id = ?',
            array($_SESSION['sess_user_id'])
        );

        db_execute_prepared(
            'UPDATE device_type
                SET name = ?, 
                updated_at = CURRENT_TIMESTAMP(),
                supplier_id = ?,
                edited_by = ?
                WHERE id = ?',
            array($nameTrim, $supplierId, $username, $id)
        );
        header("Location: " . $_SERVER['REQUEST_URI']);
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
        $user = db_fetch_assoc_prepared('SELECT username
                                            FROM user_auth 
                                            WHERE id = ?', array($_SESSION['sess_user_id']));
        db_execute_prepared(
            'INSERT INTO device_type
                (name, supplier_id, owner, edited_by) VALUES (?, ?, ?, ?)',
            array($nameTrim, $supplierId, $user[0]['username'], $user[0]['username'])
        );
        header("Location: " . $_SERVER['REQUEST_URI']);
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
        <form id='form_device_type' action='sites.php'>
            <table class='filterTable'>
                <tr>
                    <td>
                        <?php print __('Search'); ?>
                    </td>
                    <td>
                        <input type='text' class='ui-state-default ui-corner-all' id='filter' size='25' value='<?php print html_escape_request_var('search'); ?>'>
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
                strURL = 'device_type.php?header=false';
                strURL += '&search=' + $('#filter').val();
                loadPageNoHeader(strURL);
            }

            function clearFilter() {
                strURL = 'device_type.php?clear=1&header=false';
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

<section>
    <p style="color:red;"><?= isset($_GET['message']) ? $_GET['message'] : '' ?></p>
    <table class="cactiTable" style="width:100%">
        <tbody>
            <tr class="tableHeader">
                <th>#</th>
                <th>Device type</th>
                <th>Supplier</th>
                <th>Owner</th>
                <th>Last Edited</th>
                <th>Edited By</th>
                <th>Action</th>
            </tr>
            <?= drawBodyTable(); ?>
        </tbody>
    </table>
</section>

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
                            <input type="hidden" id="supplier_id-update" name="supplier-id" />
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
    document.querySelectorAll('.device_type-edit').forEach(element => {
        element.addEventListener('click', (el) => {
            const tr = el.target.parentNode.parentNode;
            document.getElementById('device_type-name').value = tr.getAttribute('name');
            document.getElementById('device_type-id').value = tr.getAttribute('id');
            $('#supplier_id-update').attr('supplier_id-update', tr.getAttribute('supplier'));
            $('#supplier_select').val(tr.getAttribute('supplier'));
            $('#supplier_select-button .ui-selectmenu-text').text(tr.getAttribute('supplier-name'));
        })
    });

    document.querySelectorAll('.device_type-delete').forEach(element => {
        element.addEventListener('click', (el) => {
            const tr = el.target;
            const id = tr.parentNode.parentNode.getAttribute('id');
            localStorage.setItem('delete_id', id);
        })
    });

    $(document).ready(function() {
        $('#ex1dt').on('modal:close', () => {
            document.getElementById('device_type-name').value = '';
            $('#supplier_select').val('None')
            $('#supplier_select-button .ui-selectmenu-text').text('None')
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
                $.post('device_type.php?action=ajax_delete', {
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

        $('#device_type-modal').submit((e) => {
            $('.area-second button[type=submit]').removeAttr('disabled');
            $('.area-second button[type=submit]').removeClass('ui-button-disabled ui-state-disabled');
            let supplierSelectValue = $('#supplier_select').val();
            let inputTextValue = $('#device_type-name').val();
            let isValidate = supplierSelectValue === 'None' || inputTextValue === ''

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

            if (inputTextValue === '') {
                $('#device_type-name').css({
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