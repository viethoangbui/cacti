<?php
require_once('./include/auth.php');
require_once('./include/myCommond.php');

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

if (get_request_var('action') === 'clear' || !isset($_GET['page'])) {
    unset($_SESSION['common_sort_column'], $_SESSION['common_sort_direction']);
}

top_header();
initial();
?>
<link rel="stylesheet" href="./include/css/supplier.css" />
<?php

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
            array($nameTrim, $_SESSION['sess_user_id'], $id)
        );
        $page = $_GET['current_page'] ?? '';
        $url = $_SERVER['SCRIPT_NAME'] . "?page=$page";
        header("Location: " . $url);
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
        $page = $_GET['last_page'] ?? '';
        $url = $_SERVER['SCRIPT_NAME'] . "?page=$page";
        header("Location: " . $url);
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
<tr class='even noprint'>
    <td>
        <form id='form_supplier' action=''>
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
                strURL = 'supplier.php';
                strURL += '?filter=' + $('#filter').val();
                strURL += '&header=false';
                loadPageNoHeader(strURL);
            }

            function clearFilter() {
                strURL = 'supplier.php?action=clear&header=false';
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


<?php
$display_text = array(
    'nosort1' => array(
        'display' => __('#'),
        'align' => 'left',
        'tip' => __('Order')
    ),
    'name' => array(
        'display' => __('Supplier Name'),
        'align' => 'left',
        'sort' => 'ASC',
        'tip' => __('supplier Name.')
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
        'sort' => 'ASC'
    ),
    'edited_by' => array(
        'display' => __('Edited By'),
        'align' => 'left',
        'sort' => 'ASC'
    ),
    'nosort2' => array(
        'display' => __('Status'),
        'align' => 'left',
        'tip' => __('View.')
    ),
    'nosort3' => array(
        'display' => __('Action'),
        'align' => 'left',
        'tip' => __('Actions.')
    ),
);
$display_text_size = sizeof($display_text);
$display_text = api_plugin_hook_function('supplier_display_text', $display_text);
$limit = 15;
$pageDefault = 1;
$page = isset($_GET['page']) ? convertStrPreventXss($_GET['page']) : $pageDefault;
$page = (int)$page !== 0 ? (int)$page : $pageDefault;

$offset = ($page - 1) * $limit;
$sql = "SELECT suppliers.*, 
        ua1.username AS edited_name_by,
        ua2.username AS owner_name
        FROM suppliers 
        LEFT JOIN user_auth AS ua1 ON suppliers.edited_by = ua1.id
        LEFT JOIN user_auth AS ua2 ON suppliers.owner = ua2.id
        WHERE suppliers.is_enable = 1";

$searchFilter = !empty($_GET['filter']) ? '%' . html_escape_request_var('filter') . '%' : null;

$sortColumn = !empty($_GET['sort_column']) ? $_GET['sort_column'] : $_SESSION['common_sort_column'] ?? null;
$sortDirection = !empty($_GET['sort_direction']) ? $_GET['sort_direction'] : $_SESSION['common_sort_direction'] ?? null;

if ($sortColumn) $_SESSION['common_sort_column'] = $sortColumn;
if ($sortDirection) $_SESSION['common_sort_direction'] = $sortDirection;

if ($searchFilter) {
    $sql .= " AND suppliers.name LIKE ?";
}

if ($sortColumn && $sortDirection) {
    $sql .= " ORDER BY suppliers.$sortColumn $sortDirection";
}
$sql .= " LIMIT $offset, $limit";

$suppliers = $searchFilter ? db_fetch_assoc_prepared($sql, array($searchFilter)) : db_fetch_assoc($sql);
$total = !$searchFilter ? db_fetch_cell("SELECT COUNT(*) FROM suppliers WHERE is_enable = 1")
    : db_fetch_cell_prepared("SELECT COUNT(*) FROM suppliers WHERE is_enable = 1 AND name LIKE ?", [$searchFilter]);
$pageCount = ceil($total / $limit);
form_start('supplier.php', 'chk');
?>
<?php

html_start_box('', '100%', '', '3', 'center', '');
html_header_sort($display_text, $sortColumn, $sortDirection, false);

if (sizeof($display_text) != $display_text_size && cacti_sizeof($suppliers)) { //display_text changed
    api_plugin_hook_function('supplier_table_replace', $suppliers);
} else if (cacti_sizeof($suppliers)) {
    foreach ($suppliers as $index => $value) {
        form_alternate_row($value['id'], true);
        form_selectable_cell(filter_value((($page - 1) * $limit + 1) + $index, get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value($value['name'], get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value($value['owner_name'], get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value($value['updated_at'], get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value($value['edited_name_by'], get_request_var('filter')), $value['id']);
        form_selectable_cell(filter_value('Enable', get_request_var('filter')), $value['id']);
        echo "<td class=\"nowrap\">
                                <a class=\"supplier-edit\" 
                                    style=\"cursor:pointer;\"
                                    href=\"#ex1\" rel=\"modal:open\"
                                    >Edit</a>
                                <a class=\"supplier-delete\" 
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
            <?= (($page - 1) * $limit) + 1 ?> to <?= (($page - 1) * $limit) + count($suppliers) ?> of <?= $total ?>
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
                strURL = 'supplier.php' + $(event.target).attr('url') + '&header=false';
                loadPageNoHeader(strURL);
            })
        })
    </script>
<?php endif; ?>

<section>
    <div id="ex1" class="modal">
        <div class="modal-content">
            <h2 style="margin-top: 0px;">Supplier Create</h2>
            <div class="modal-body">
                <form action="?last_page=<?= ceil(($total + 1) / $limit) ?>&current_page=<?= $page ?>" method="post" id="supplier-modal">
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
            document.getElementById('supplier-name').value = tr.children[1].textContent;
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
            const ivaluedate = document.getElementById('supplier-name').value;
            if (!ivaluedate) {
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