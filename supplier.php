<?php
require_once('./include/auth.php');

if (get_request_var('action') === 'ajax_delete') {
    $id = get_filter_request_var('delete_id');

    $check = db_fetch_cell_prepared(
        'SELECT count(*)
            FROM device_type
            WHERE supplier_id = ? and is_enable = 1',
        array($id)
    );

    if ($check == 0) {
        $isEnable = db_fetch_cell_prepared('SELECT is_enable FROM suppliers WHERE id = ?', array($id));
        $enableInsert = $isEnable == "1" ? "0" : "1";

        db_execute_prepared(
            'UPDATE suppliers
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
<link rel="stylesheet" href="./include/css/supplier.css" />
<?php
function drawBodyTable()
{
    $sql = 'SELECT * FROM suppliers WHERE is_enable = 1';

    if (!empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $sql .= " AND name LIKE ?";
        $data = db_fetch_assoc_prepared($sql, array($search));
    } else {
        $data = db_fetch_assoc($sql);
    }

    $users = db_fetch_assoc('SELECT id, username FROM user_auth');

    foreach ($data as $key => $value) {
        foreach ($users as $user) {
            if ($value['owner'] == $user['id']) {
                $data[$key]['owner'] = $user['username'];
            }

            if ($value['edited_by'] == $user['id']) {
                $data[$key]['edited_by'] = $user['username'];
            }
        }
    }

    if (count($data) === 0) {
        return "<tr class='tableRow'><td colspan='" . (3) . "'><em>" . __('No Supplier Found') . "</em></td></tr>\n";
    }

    $result = '';

    foreach ($data as $index => $value) {
        $count = $index + 1;

        $result .= "
                        <tr class=\"odd selectable tableRow\" id=\"{$value["id"]}\" name=\"{$value["name"]}\">
                            <td class=\"nowrap\">
                                {$count}
                            </td>
                            <td class=\"nowrap supplier-name\">
                                {$value["name"]}
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
                                Enable
                            </td>
                            <td class=\"nowrap\">
                                <a class=\"supplier-edit\" 
                                    style=\"cursor:pointer;\"
                                    href=\"#ex1\" rel=\"modal:open\"
                                    >Edit</a>
                                <a class=\"supplier-delete\" 
                                    style=\"cursor:pointer;\"
                                    href=\"#ex2\" rel=\"modal:open\"
                                >Delete</a>
                            </td>
                        </tr>
                        ";
    }

    return $result;
}

function update($input, $id)
{
    $pattern = '/<[^>]*>|javascript:[^"\'\s]*|data:[^"\'\s]*|on\w+\s*=\s*[^"\'\s]*/i';

    // Remove potential XSS vectors
    $name = preg_replace($pattern, '', $input);

    $nameTrim = trim(
        trim(htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), ' ')
    );

    if (!$nameTrim) {
        return;
    }

    $names = db_fetch_cell_prepared(
        'SELECT count(*)
            FROM suppliers
            WHERE id != ? and name = ? and is_enable = 1',
        array($id, $nameTrim)
    );

    if ($names == 0) {
        db_execute_prepared(
            'UPDATE suppliers
                    SET name = ?, 
                    updated_at = CURRENT_TIMESTAMP(),
                    edited_by = ?
                    WHERE id = ?',
            array($nameTrim, $_SESSION['sess_user_id'],$id)
        );
        header("Location: " . $_SERVER['REQUEST_URI']);
    } else {
        raise_message('name_used');
    }
}

function create($input)
{
    $pattern = '/<[^>]*>|javascript:[^"\'\s]*|data:[^"\'\s]*|on\w+\s*=\s*[^"\'\s]*/i';

    // Remove potential XSS vectors
    $name = preg_replace($pattern, '', $input);

    $nameTrim = trim(
        trim(htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), ' ')
    );

    if (!$nameTrim) {
        return;
    }

    $total = db_fetch_cell_prepared(
        'SELECT COUNT(*)
            FROM suppliers
            WHERE name = ? and is_enable = 1
			',
        array($nameTrim)
    );

    if ($total >= 1) {
        raise_message('name_used');
    } else {
        db_execute_prepared(
            'INSERT INTO suppliers
                (name, owner, edited_by, is_enable) VALUES (?,?,?,?)',
            array($nameTrim, $_SESSION['sess_user_id'], $_SESSION['sess_user_id'], 1)
        );
        header("Location: " . $_SERVER['REQUEST_URI']);
    }
}

function initial()
{
    if (isset($_POST['name'])) {
        $action = !empty($_POST['id']) ? 'update' : 'create';

        switch ($action) {
            case 'update':
                update($_POST['name'], $_POST['id']);
                break;
            case 'create':
                create($_POST['name']);
                break;
            default:
                break;
        }
    }
}

html_start_box(__('Suppliers'), '100%', '', '3', 'center', [
    [
        'href' => '#ex1',
        'rel' => 'modal:open'
    ]
]);
?>
<tr class='even'>
    <td>
        <form id='form_supplier' action='sites.php'>
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
                strURL = 'supplier.php?header=false';
                strURL += '&search=' + $('#filter').val();
                loadPageNoHeader(strURL);
            }

            function clearFilter() {
                strURL = 'supplier.php?clear=1&header=false';
                loadPageNoHeader(strURL);
            }

            $(function() {
                $('#refresh').click(function() {
                    applyFilter();
                });

                $('#clear').click(function() {
                    clearFilter();
                });

                $('#form_supplier').submit(function(event) {
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
                <th>Supplier Name</th>
                <th>Owner</th>
                <th>Last Edited</th>
                <th>Edited By</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php echo drawBodyTable(); ?>
        </tbody>
    </table>
</section>

<section>
    <div id="ex1" class="modal">
        <div class="modal-content">
            <h2 style="margin-top: 0px;">Supplier Create</h2>
            <div class="modal-body">
                <form action="" method="post" id="supplier-modal">
                    <div class="area-frist">
                        <label for="supplier-name" class="col-form-label">Name:</label>
                        <input type="text"
                            class="form-control"
                            id="supplier-name"
                            placeholder="Enter name"
                            name="name">
                        <input type="hidden" id="supplier-id" name="id" />
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
                <p>Bạn có chắc chắn muốn xóa không?</p>
                <div>
                    <a href="#" rel="modal:close">
                        <button>Close</button>
                    </a>
                    <button
                        type="button"
                        class="btn btn-danger">
                        Ok
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.querySelectorAll('.supplier-edit').forEach(element => {
        element.addEventListener('click', (el) => {
            const tr = el.target.parentNode.parentNode;
            document.getElementById('supplier-name').value = tr.getAttribute('name');
            document.getElementById('supplier-id').value = tr.getAttribute('id');
        })
    });

    document.querySelectorAll('.supplier-delete').forEach(element => {
        element.addEventListener('click', (el) => {
            const tr = el.target;
            const id = tr.parentNode.parentNode.getAttribute('id');
            localStorage.setItem('delete_id', id);
        })
    });

    $(document).ready(function() {
        $('#ex1').on('modal:close', () => {
            document.getElementById('supplier-name').value = '';
        })

        $('#ex1').on('modal:open', () => {
            const isUpdate = document.getElementById('supplier-name').value;
            if (!isUpdate) {
                $('.modal-content h2').text('Supplier Create')
            } else {
                $('.modal-content h2').text('Supplier Update')
            }
        })

        $('#ex1 .area-second a').click(() => {
            document.getElementById('supplier-name').value = '';
        })

        $('.btn-danger').on('click', () => {
            const deleteId = localStorage.getItem('delete_id', id);

            if (deleteId) {
                $.post('supplier.php?action=ajax_delete', {
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

        $('#supplier-modal').submit((e) => {
            $('.area-second button[type=submit]').removeAttr('disabled');
            $('.area-second button[type=submit]').removeClass('ui-button-disabled ui-state-disabled');
            let inputValue = $('#supplier-name').val();
            let isValidate = inputValue === ''

            if (isValidate) {
                e.preventDefault();
                $('#supplier-name').css({
                    'border': '1px solid red'
                })
                sessionMessage = {
                    message: '<?php print __esc('Bạn cần điền đầy đủ các trường'); ?>',
                    level: MESSAGE_LEVEL_ERROR
                };
                displayMessages()
            }
        })

        $('#supplier-name').on('change keydown', (e) => {
            if (e.target.value !== '') {
                $('#supplier-name').css({
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