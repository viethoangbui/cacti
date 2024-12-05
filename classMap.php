<?php
include('./include/auth.php');
include('./include/myCommond.php');
switch (get_request_var('action')) {
    case 'update':
        $status = isset($_POST['status']) ? 1 : 0;
        update($_POST['ids'], $_POST['cl'], $status);
        break;
    case 'export':
        classmap_export();
        break;
    case 'clear':
        unset($_SESSION['cl_sort_column'], $_SESSION['cl_sort_direction']);
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
    $url = "Location: " . $_SERVER['SCRIPT_NAME'];
    if (isset($_GET['page'])) {
        $url .= "?page={$_GET['page']}";
    }

    if (!isValidFormat($ids)) {
        header($url);
    }

    $ids = convertStrPreventXss($ids);
    $username = db_fetch_cell_prepared(
        'SELECT username
        FROM user_auth
        WHERE id = ?',
        array($_SESSION['sess_user_id'])
    );

    db_execute_prepared(
        "UPDATE class_map
                SET cl_number = ?, user_update = ?, status = ?
                WHERE id IN ($ids)",
        [convertStrPreventXss($clNumber), $username, convertStrPreventXss($status)]
    );
    header($url);
}

function classmap_export()
{
    $classMaps = db_fetch_assoc("SELECT * FROM class_map");

    $stdout = fopen('php://output', 'w');

    header('Content-type: application/excel');
    header('Content-Disposition: attachment; filename=cacti-classmap-' . time() . '.csv');

    if (cacti_sizeof($classMaps)) {
        $columns = array_keys($classMaps[0]);
        fputcsv($stdout, $columns);

        foreach ($classMaps as $c) {
            fputcsv($stdout, $c);
        }
    }

    fclose($stdout);
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
                    strURL = 'classMap.php?action=clear&header=false';
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
        'nosort2' => array(
            'display' => __('Class Map'),
            'align' => 'left',
            'tip' => __('View.')
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
    $sql = "SELECT * FROM class_map";

    $searchFilter = !empty($_GET['filter']) ? '%' . html_escape_request_var('filter') . '%' : null;

    $sortColumn = !empty($_GET['sort_column']) ? $_GET['sort_column'] : $_SESSION['cl_sort_column'] ?? null;
    $sortDirection = !empty($_GET['sort_direction']) ? $_GET['sort_direction'] : $_SESSION['cl_sort_direction'] ?? null;

    if ($sortColumn) $_SESSION['cl_sort_column'] = $sortColumn;
    if ($sortDirection) $_SESSION['cl_sort_direction'] = $sortDirection;

    if ($searchFilter) {
        $sql .= " WHERE ip_address LIKE ? OR class_map_name LIKE ?";
    }

    if ($sortColumn && $sortDirection) {
        $sql .= " ORDER BY $sortColumn $sortDirection";
    }
    $sql .= " LIMIT $offset, $limit";

    $classMaps = $searchFilter ? db_fetch_assoc_prepared($sql, array($searchFilter, $searchFilter)) : db_fetch_assoc($sql);
    $total = !$searchFilter ? db_fetch_cell("SELECT COUNT(*) FROM class_map")
        : db_fetch_cell_prepared("SELECT COUNT(*) FROM class_map WHERE ip_address LIKE ?", [$searchFilter]);
    $pageCount = ceil($total / $limit);
    form_start('classMap.php', 'chk');
    ?>
    <?php

    html_start_box('', '100%', '', '3', 'center', '');
    html_header_sort_checkbox($display_text, $sortColumn, $sortDirection, false);

    if (sizeof($display_text) != $display_text_size && cacti_sizeof($classMaps)) { //display_text changed
        api_plugin_hook_function('classmap_table_replace', $classMaps);
    } else if (cacti_sizeof($classMaps)) {
        foreach ($classMaps as $class) {
            form_alternate_row('line' . $class['id'], true);
            echo "<td class=\"edit-cell\" classId=\"{$class['id']}\" ip=\"{$class['ip_address']}\"
                    clnumber=\"{$class['cl_number']}\" status=\"{$class['status']}\" style=\"cursor:pointer;\"> 
				<a><svg xmlns='http://www.w3.org/2000/svg' height='18px' viewBox='0 -960 960 960' width='18px' fill='#EE0033'>
                <path d='M200-200h57l391-391-57-57-391 391v57Zm-80 80v-170l528-527q12-11 26.5-17t30.5-6q16 0 31 6t26 18l55 56q12 11 17.5 26t5.5 30q0 16-5.5 30.5T817-647L290-120H120Zm640-584-56-56 56 56Zm-141 85-28-29 57 57-29-28Z'/></svg>
                </a>
                </td>";
            form_selectable_cell(filter_value($class['ip_address'], get_request_var('filter')), $class['id']);
            form_selectable_cell(filter_value($class['class_map_key'], get_request_var('filter')), $class['id']);
            form_selectable_cell(filter_value($class['class_map_name'], get_request_var('filter')), $class['id']);
            echo "<td class=\"class-map-view\" style=\"cursor:pointer;\"> 
				<a href='#'>Graph</a>
                </td>";
            form_selectable_cell(filter_value($class['cl_number'], get_request_var('filter')), $class['id']);
            form_selectable_cell(filter_value($class['user_create'], get_request_var('filter')), $class['id']);
            form_selectable_cell(filter_value($class['user_update'], get_request_var('filter')), $class['id']);
            form_selectable_cell(filter_value($class['time_create'], get_request_var('filter')), $class['id']);
            form_selectable_cell(filter_value($class['time_update'], get_request_var('filter')), $class['id']);
            form_selectable_cell(filter_value($class['status'] ? 'Enable' : 'Disable', get_request_var('filter')), $class['id']);
            form_checkbox_cell($class['status'], $class['id']);
            form_end_row();
        }
    } else {
        print "<tr class='tableRow'><td colspan='" . (cacti_sizeof($display_text) + 1) . "'><em>" . __('No ClassMap Found') . "</em></td></tr>";
    }

    html_end_box(false);

    form_end();
    api_plugin_hook('device_table_bottom');
    $showDots = false;

    ?>
    <?php if ($pageCount > 1): ?>
        <div class="navBarNavigation" style="margin:12px 0;">
            <div class="navBarNavigationCenter">
                <?= (($page - 1) * $limit) + 1 ?> to <?= (($page - 1) * $limit) + count($classMaps) ?> of <?= $total ?>
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
                    strURL = 'classMap.php' + $(event.target).attr('url') + '&header=false';
                    loadPageNoHeader(strURL);
                })
            })
        </script>
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
                    <form id="form-update"
                        action="<?= "classMap.php?action=update" . (isset($_GET['page']) ? "&page={$_GET['page']}" : '') ?>" method="post">
                        <input type="hidden" name="ids" />
                        <section class="edit-form">
                            <div style="display: flex;" id="modal-filed-ip-selected">
                                <label for="cl" style="width: 20%;display:inline-block">IP selected</label>
                                <table class="ip-address-selected" 
                                        style="margin: 0 0 8px 4px;">
                                    <tbody 
                                        style="width:267.45px;display:block;overflow:auto;transform:rotateX(180deg);">
                                        <tr></tr>
                                        <tr></tr>
                                    </tbody>
                                </table>
                            </div>
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
            // handle click modal
            $('.modal-edit-toggle').on('click', function(e) {
                e.preventDefault();
                $('.modal-edit').toggleClass('is-visible');
            })

            //handle click button edit
            $('.edit-cell').on('click', function(event) {
                event.stopPropagation();

                updateModal(
                    $(this).attr('classid'),
                    $(this).attr('clnumber'),
                    $(this).attr('status')
                )
                $('.modal-edit').toggleClass('is-visible');
            });

            $('.class-map-view').on('click', function(event) {
                event.stopPropagation();
            });

            // click edit button multi select
            $('#btn-edit-classmap').on('click', () => {
                $('.modal-edit').toggleClass('is-visible')
                updateModal(arrSessionStorage.join(', '), "", 1, ipSessionStorage)
            })

            // fill row selected
            $('tr[id^="line"]').filter(':not(.disabled_row)').each((_index, element) => {
                if (arrSessionStorage.length > 0) {
                    const id = Number($(element).children('.edit-cell').attr('classid'))
                    if (arrSessionStorage.includes(id)) {
                        $(element).toggleClass('selected');
                        $(element).find(':checkbox').prop('checked', true)
                            .attr('aria-checked', 'true')
                            .attr('data-prev-check', 'true');
                    }

                }
            })

            $('#form-update').submit(() => {
                sessionStorage.removeItem('class_map_ids')
            })

            enableEditButton()
        })
    </script>
<?php
}
