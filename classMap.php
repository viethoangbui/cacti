<?php
include('./include/auth.php');
include('./include/myCommond.php');

switch (get_request_var('action')) {
    case 'update':
        $status = isset($_POST['status']) ? 1 : 0;
        update($_POST['ids'], $_POST['cl'], $status);

        break;
    default:
        top_header();

        classMap();

        bottom_footer();
        break;
}

function isValidFormat($input)
{
    $pattern = '/^(\d+)(?:, \d+)*$/';
    return preg_match($pattern, $input) === 1;
}

// define function
function update($ids, $clNumber, $status)
{
    if (!isValidFormat($ids)) {
        header("Location: " . $_SERVER['SCRIPT_NAME']);
    }

    $ids = convertStrPreventXss($ids);
    $username = db_fetch_cell_prepared(
        'SELECT username
        FROM user_auth
        WHERE id = ?',
        array($_SESSION['sess_user_id'])
    );

    db_execute_prepared(
        "UPDATE classmaps
                SET cl_number = ?, user_update = ?, status = ?
                WHERE id IN ($ids)",
        [convertStrPreventXss($clNumber), $username, convertStrPreventXss($status)]
    );
    header("Location: " . $_SERVER['SCRIPT_NAME']);
}

function classMap()
{
    $url = 'classMap.php?action=edit';

    html_start_box(__('ClassMap'), '100%', '', '3', 'center', $url);

?>
    <tr class='even noprint'>
        <td>
            <form id='form_ClassMap' action='classMap.php'>
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
                                <input type='submit' class='ui-button ui-corner-all ui-widget' id='go' value='<?php print __('Go'); ?>' title='<?php print __esc('Set/Refresh Filters'); ?>'>
                                <input type='button' class='ui-button ui-corner-all ui-widget' id='clear' value='<?php print __('Clear'); ?>' title='<?php print __esc('Clear Filters'); ?>'>
                                <input type='button' class='ui-button ui-corner-all ui-widget' id='export' value='<?php print __('Export'); ?>' title='<?php print __esc('Export ClassMap'); ?>'>
                            </span>
                        </td>
                    </tr>
                </table>
            </form>
            <script type='text/javascript'>
                function applyFilter() {
                    strURL = 'classMap.php';
                    strURL += '?filter=' + $('#filter').val();
                    strURL += '&header=false';
                    loadPageNoHeader(strURL);
                }

                function clearFilter() {
                    strURL = 'classMap.php?clear=1&header=false';
                    loadPageNoHeader(strURL);
                }

                function exportRecords() {
                    strURL = 'classMap.php?action=export';
                    document.location = strURL;
                    Pace.stop();
                }

                $(function() {
                    $('#clear').click(function() {
                        clearFilter();
                    });

                    $('#export').click(function() {
                        exportRecords();
                    });

                    $('#form_ClassMap').submit(function(event) {
                        event.preventDefault();
                        applyFilter();
                    });
                });
            </script>
        </td>
    </tr>
    <?php

    html_end_box();
    ?>
    <div class="row-graph-selector" style="display: flex;align-items:center;gap:8px;">
        <button type="button"
            class="ui-button ui-widget ui-state-active"
            id="btn-edit-classmap"
            style="margin:4px 0 6px 0;border:none;outline:none;display:none;">Edit</button>
        <p id="show-graph-added" style="margin:0;margin-bottom:4px;display:none;color:crimson;cursor:pointer;"></p>
    </div>
    <?php
    $display_text = array(
        'nosort1' => array(
            'display' => __(' '),
            'align' => 'left',
            'tip' => __('Edit host.')
        ),
        'ip_address' => array(
            'display' => __('Ip Address'),
            'align' => 'left',
            'sort' => 'ASC',
            'tip' => __('The name by which this Device will be referred to.')
        ),
        'class_map_key' => array(
            'display' => __('Class Map Key'),
            'align' => 'left',
            'sort' => 'ASC',
            'tip' => __('Either an IP address, or hostname.  If a hostname, it must be resolvable by either DNS, or from your hosts file.')
        ),
        'class_map_name' => array(
            'display' => __('Class Map Name'),
            'align' => 'left',
            'sort' => 'ASC'
        ),
        'cl_number' => array(
            'display' => __('Cl Number'),
            'align' => 'left',
            'sort' => 'ASC'
        ),
        'user_create' => array(
            'display' => __('User Create'),
            'align' => 'left',
            'sort' => 'ASC'
        ),
        'user_update' => array(
            'display' => __('User Update'),
            'align' => 'left',
            'sort' => 'ASC'
        ),
        'time_create' => array(
            'display' => __('Time Create'),
            'align' => 'right',
            'sort' => 'ASC',
            'tip' => __('The internal database ID for this Device.  Useful when performing automation or debugging.')
        ),
        'time_update' => array(
            'display' => __('Time Update'),
            'align' => 'right',
            'sort' => 'DESC',
            'tip' => __('The total number of Graphs generated from this Device.')
        ),
        'status' => array(
            'display' => __('Status'),
            'align' => 'right',
            'sort' => 'DESC',
            'tip' => __('The total number of Data Sources generated from this Device.')
        ),
    );

    $display_text_size = sizeof($display_text);
    $display_text = api_plugin_hook_function('classmap_display_text', $display_text);
    $limit = 30;
    $pageDefault = 1;
    $page = isset($_GET['page']) ? convertStrPreventXss($_GET['page']) : $pageDefault;
    $page = (int)$page !== 0 ? (int)$page : $pageDefault;

    $offset = ($page - 1) * $limit;
    $sql = "SELECT * FROM classmaps";

    $searchFilter = !empty($_GET['filter']) ? '%' . html_escape_request_var('filter') . '%' : null;

    $sortColumn = !empty($_GET['sort_column']) ? $_GET['sort_column'] : null;
    $sortDirection = !empty($_GET['sort_direction']) ? $_GET['sort_direction'] : null;

    if ($searchFilter) {
        $sql .= " WHERE ip_address LIKE ?";
    }

    if ($sortColumn && $sortDirection) {
        $sql .= " ORDER BY $sortColumn $sortDirection";
    }
    $sql.= " LIMIT $offset, $limit";

    $classMaps = $searchFilter ? db_fetch_assoc_prepared($sql, array($searchFilter)) : db_fetch_assoc($sql);
    $total = db_fetch_cell("SELECT COUNT(*) FROM classmaps");
    $pageCount = ceil($total / $limit);
    form_start('classMap.php', 'chk');
    ?>
    <?php

    html_start_box('', '100%', '', '3', 'center', '');
    html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

    if (sizeof($display_text) != $display_text_size && cacti_sizeof($classMaps)) { //display_text changed
        api_plugin_hook_function('classmap_table_replace', $classMaps);
    } else if (cacti_sizeof($classMaps)) {
        foreach ($classMaps as $class) {
            form_alternate_row('line' . $class['id'], true);
            echo "<td class=\"edit-cell\" classId=\"{$class['id']}\" clnumber=\"{$class['cl_number']}\" status=\"{$class['status']}\"> 
				<a><svg xmlns='http://www.w3.org/2000/svg' height='18px' viewBox='0 -960 960 960' width='18px' fill='#EE0033'>
                <path d='M200-200h57l391-391-57-57-391 391v57Zm-80 80v-170l528-527q12-11 26.5-17t30.5-6q16 0 31 6t26 18l55 56q12 11 17.5 26t5.5 30q0 16-5.5 30.5T817-647L290-120H120Zm640-584-56-56 56 56Zm-141 85-28-29 57 57-29-28Z'/></svg>
                </a>
                </td>";
            form_selectable_cell(filter_value($class['ip_address'], get_request_var('filter')), $class['id']);
            form_selectable_cell(filter_value($class['class_map_key'], get_request_var('filter')), $class['id']);
            form_selectable_cell(filter_value($class['class_map_name'], get_request_var('filter')), $class['id']);
            form_selectable_cell(filter_value($class['cl_number'], get_request_var('filter')), $class['id']);
            form_selectable_cell(filter_value($class['user_create'], get_request_var('filter')), $class['id']);
            form_selectable_cell(filter_value($class['user_update'], get_request_var('filter')), $class['id']);
            form_selectable_cell(filter_value($class['time_create'], get_request_var('filter')), $class['id']);
            form_selectable_cell(filter_value($class['time_update'], get_request_var('filter')), $class['id']);
            form_selectable_cell(filter_value($class['status'] ? 'Up' : 'Down', get_request_var('filter')), $class['id']);
            form_checkbox_cell($class['status'], $class['id']);
            form_end_row();
        }
    } else {
        print "<tr class='tableRow'><td colspan='" . (cacti_sizeof($display_text) + 1) . "'><em>" . __('No ClassMap Found') . "</em></td></tr>";
    }

    html_end_box(false);

    form_end();
    api_plugin_hook('device_table_bottom');
    ?>
    <?php if ($pageCount > 1): ?>
        <div class="navBarNavigation" style="margin:12px 0;">
            <div class="navBarNavigationCenter">
                <?= (($page - 1) * $limit) + 1 ?> to <?= (($page - 1) * $limit) + count($classMaps) ?> of <?= $total ?>
                [ <ul class="pagination">
                    <?php for ($i = 0; $i < $pageCount; $i++): ?>
                        <li>
                            <a href="?page=<?= $i + 1 ?>" class="<?= $page === ($i + 1) ? 'active' : '' ?>">
                                <?= $i + 1 ?></a>
                        </li>
                    <?php endfor; ?>
                </ul> ]
            </div>
        </div>
    <?php endif; ?>
<?php
    renderModal();
}

function renderModal()
{
?>
    <div class="modal-edit">
        <div class="modal-edit-overlay modal-edit-toggle"></div>
        <div class="modal-edit-wrapper modal-edit-transition" style="width: 30em;position:fixed;top:25%;left:50%;">
            <div class="modal-edit-header">
                <button class="modal-edit-close modal-edit-toggle">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#EE0033">
                        <path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z" />
                    </svg>
                </button>
                <h2 class="modal-edit-heading">Edit ClassMap</h2>
            </div>

            <div class="modal-edit-body">
                <div class="modal-edit-content">
                    <form id="form-update" action="classMap.php?action=update" method="post">
                        <input type="hidden" name="ids" />
                        <section class="edit-form">
                            <div>
                                <label for="cl" style="width: 20%;display:inline-block">CL Number</label>
                                <input id="cl" name="cl" type="text" style="width: 70%;" />
                            </div>
                            <div style="display:flex;align-items:center;margin-top:12px;">
                                <label for="status" style="width: 21%;">Status</label>
                                <div class="formData">
                                    <span class="nowrap">
                                        <label class="checkboxSwitch" title="Switch status">
                                            <input title="Switch status"
                                                type="checkbox" id="status"
                                                name="status">
                                            <span class="checkboxSlider checkboxRound"></span>
                                        </label>
                                        <label class="checkboxLabel" for="status">Switch status</label>
                                    </span>
                                </div>
                            </div>
                        </section>
                        <div>
                            <div style="width: fit-content;margin:0 auto;margin-top:8px;">
                                <button class="btn-update" style="padding:6px;">
                                    Submit
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(function() {
            function updateModal(ids, clText = null, status) {
                $('input[name=ids]').val(ids)
                $('#cl').val(clText)

                if (Number(status)) {
                    $('input[name=status]').attr('checked', 1)
                }

                if (!Number(status)) {
                    $('input[name=status]').removeAttr('checked')
                }

            }

            $('.modal-edit-toggle').on('click', function(e) {
                e.preventDefault();
                $('.modal-edit').toggleClass('is-visible');
            })

            $('.edit-cell').on('click', function(event) {
                event.stopPropagation();

                updateModal(
                    $(this).attr('classid'),
                    $(this).attr('clnumber'),
                    $(this).attr('status')
                )
                $('.modal-edit').toggleClass('is-visible');
            });

            $('tr[id^="line"]').filter(':not(.disabled_row)').off('click').on('click', function(event) {
                if (!$(this).children('td:first').attr('clnumber')) {
                    selectUpdateRow(event, $(this));

                    if ($(this).hasClass('selected')) {
                        $('#classMap2_child .tableRow').each(function(index, element) {
                            if ($(element).hasClass('selected') === true) {
                                $('#btn-edit-classmap').show("fast")
                            }
                        })
                    } else {
                        let count = 0;
                        $('#classMap2_child .tableRow').each(function(index, element) {
                            if ($(element).hasClass('selected') === false) {
                                count++
                            }
                        })

                        if ($('#classMap2_child .tableRow').length === count) {
                            $('#btn-edit-classmap').hide("fast")
                        }
                    }
                }
            });

            $('#btn-edit-classmap').on('click', () => {
                $('.modal-edit').toggleClass('is-visible')
                let ids = []
                let clNumber = null

                $('#classMap2_child .tableRow').each(function(index, element) {
                    if ($(element).hasClass('selected') === true) {
                        ids.push($(element).children('.edit-cell').attr('classid'))

                        if (clNumber === null) {
                            clNumber = $(element).children('.edit-cell').attr('clnumber')
                        } else {
                            clNumber = ''
                        }
                    }
                })

                let idsString = ids.join(', ')
                updateModal(idsString, clNumber)
            })
        })
    </script>
<?php
}
