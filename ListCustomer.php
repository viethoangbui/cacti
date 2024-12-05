<?php
include('./include/auth.php');

switch (get_request_var('action')) {
	case 'ajax_locations':
		get_site_locations();
		break;
	default:
		top_header();
		host();
		bottom_footer();
		break;
}
function get_site_locations()
{
	$return  = array();
	$term    = get_nfilter_request_var('term');
	$host_id = $_SESSION['cur_device_id'];
	$args  = ["%$term%"];
	$where = '';

	if (read_config_option('site_location_filter') && $_SESSION['cur_device_id']) {
		$site_id = db_fetch_cell_prepared(
			'SELECT site_id
			FROM host
			WHERE id = ?',
			array($host_id)
		);
		$args[] = $site_id;
		$where = 'AND site_id = ?';
	}

	$locations = db_fetch_assoc_prepared(
		"SELECT DISTINCT name
		FROM sites
		WHERE status = 1
		$where
		ORDER BY name",
		$args
	);

	if (cacti_sizeof($locations)) {
		foreach ($locations as $l) {
			$return[] = array('label' => $l['location'], 'value' => $l['location'], 'id' => $l['location']);
		}
	}

	if (!cacti_sizeof($return)) {
		$return[] = array('label' => html_escape($term), 'value' => html_escape($term), 'id' => html_escape($term));
		$return[] = array('label' => __('None'), 'value' => '', 'id' => __('None'));
	}

	print json_encode($return);
}

function host_validate_vars()
{
	/* ================= input validation and session storage ================= */
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
		),
		'filter' => array(
			'filter' => FILTER_DEFAULT,
			'pageset' => true,
			'default' => ''
		),
		'location' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'description',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
		),
		'site_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		)
	);
	$filters = api_plugin_hook_function('device_filters', $filters);
	validate_store_request_vars($filters, 'sess_host');
	/* ================= input validation ================= */
}

function get_Customer_records(&$total_rows, $rows)
{
	$sql_where = '';
	$rows = '50';
	/* form the 'where' clause for our main sql query */
	if (get_request_var('filter') != '') {
		$sql_where = 'WHERE (
			cl_number LIKE '   . db_qstr('%' . get_request_var('filter') . '%') . '
			OR cus_name LIKE ' . db_qstr('%' . get_request_var('filter') . '%') . ')';
	}

	if (get_request_var('site_id') == '0' || get_request_var('site_id') == '-1') {
		$sql_where .= ($sql_where != '' ? ' and customer.site_id in (SELECT DISTINCT sites.id 
		FROM user_auth
		LEFT JOIN user_auth_group_members on user_auth.id = user_auth_group_members.user_id
		LEFT JOIN user_auth_group on user_auth_group_members.group_id = user_auth_group.id
		left JOIN sites on user_auth_group.description = sites.city
		WHERE sites.id is not null and user_auth.id = ' . $_SESSION['sess_user_id'] . ') '
			: ' WHERE   customer.site_id in (SELECT DISTINCT sites.id 
		FROM user_auth
		LEFT JOIN user_auth_group_members on user_auth.id = user_auth_group_members.user_id
		LEFT JOIN user_auth_group on user_auth_group_members.group_id = user_auth_group.id
		left JOIN sites on user_auth_group.description = sites.city
		WHERE sites.id is not null and user_auth.id = ' . $_SESSION['sess_user_id'] . ')');
	} else {
		$sql_where .= ($sql_where != '' ? ' AND customer.site_id=' . get_request_var('site_id') : ' WHERE customer.site_id=' . get_request_var('site_id'));
	}

	$sql = "SELECT COUNT(cl_number) FROM customer 
			$sql_where
			";
	$total_rows = get_total_row_data($_SESSION['sess_user_id'], $sql, array(), 'device');

	$sql_limit = 'LIMIT ' . ($rows * (get_request_var('page') - 1)) . ',' . $rows;

	// $sql_query = "select customer.*,sites.name as site_name
    //               from customer
	// $sql_where 
    // $sql_limit";
$sql_query = "select customer.*
              from customer";
	return db_fetch_assoc($sql_query);
}

function host()
{
	global $device_actions, $item_rows, $config;
	host_validate_vars();
	/* if the number of rows is -1, set it to the default */
	if (get_request_var('rows') == -1) {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	html_start_box(__('Devices'), '100%', '', '3', 'center', 'ListCustomer.php');

?>
	<tr class='even noprint'>
		<td>
			<form id='form_devices' action='ListCustomer.php'>
				<table class='filterTable'>
					<tr>
						<?php api_plugin_hook('device_filter_start'); ?>
						<td>
							<?php print __('Site'); ?>
						</td>
						<td>
							<select id='site_id'>
								<option value='-1' <?php if (get_request_var('site_id') == '-1') { ?> selected<?php } ?>><?php print __('Any'); ?></option>
								<?php
								$sites = db_fetch_assoc('SELECT distinct sites.id ,sites.name
													FROM user_auth
													LEFT JOIN user_auth_group_members on user_auth.id = user_auth_group_members.user_id
													LEFT JOIN user_auth_group on user_auth_group_members.group_id = user_auth_group.id
													left JOIN sites on user_auth_group.description = sites.city
													WHERE sites.id is not null and user_auth.id =' . $_SESSION['sess_user_id']);

								if (cacti_sizeof($sites)) {
									foreach ($sites as $site) {
										print "<option value='" . $site['id'] . "'";
										if (get_request_var('site_id') == $site['id']) {
											print ' selected';
										}
										print '>' . html_escape($site['name']) . '</option>';
									}
								}
								?>
							</select>
						</td>
						<td>
							<?php print __('Location'); ?>
						</td>
						<td>
							<select id='location'>
								<option value='-1' <?php if (get_request_var('location') == '-1') { ?> selected<?php } ?>><?php print __('All'); ?></option>
								<?php
								$locations = db_fetch_assoc('SELECT Distinct sites.id ,sites.name as location,sites.state
							FROM user_auth
							LEFT JOIN user_auth_group_members on user_auth.id = user_auth_group_members.user_id
							LEFT JOIN user_auth_group on user_auth_group_members.group_id = user_auth_group.id
							left JOIN sites on user_auth_group.description = sites.city
							WHERE sites.id is not null and user_auth.id =' . $_SESSION['sess_user_id']);

								if (cacti_sizeof($locations)) {
									foreach ($locations as $l) {
										print "<option value='" . $l['state'] . "'";
										if (get_request_var('location') == $l['state']) {
											print ' selected';
										}
										print '>' . html_escape($l['location']) . '</option>';
									}
								}
								?>
							</select>
						</td>
						<td>
							<span>
								<input type='submit' class='ui-button ui-corner-all ui-widget' id='go' value='<?php print __('Go'); ?>' title='<?php print __esc('Set/Refresh Filters'); ?>'>
								<input type='button' class='ui-button ui-corner-all ui-widget' id='clear' value='<?php print __('Clear'); ?>' title='<?php print __esc('Clear Filters'); ?>'>
							</span>
						</td>
					</tr>
				</table>
				<table class='filterTable'>
					<tr>
						<td>
							<?php print __('Search'); ?>
						</td>
						<td>
							<input type='text' class='ui-state-default ui-corner-all' id='filter' size='50' value='<?php print html_escape_request_var('filter'); ?>'>
						</td>
					</tr>
				</table>
			</form>
			<script type='text/javascript'>
				function applyFilter() {
					strURL = 'ListCustomer.php';
					strURL += '?site_id=' + $('#site_id').val();
					//strURL += '&rows=' + $('#rows').val();
					strURL += '&rows=50';
					strURL += '&filter=' + $('#filter').val();
					strURL += '&header=false';
					loadPageNoHeader(strURL);
				}

				function clearFilter() {
					strURL = 'ListCustomer.php?clear=1&header=false';
					loadPageNoHeader(strURL);
				}
				$(function() {
					$('#rows, #site_id').change(function() {
						applyFilter();
					});

					$('#clear').click(function() {
						clearFilter();
					});
				});
				$('#form_devices').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});
			</script>
		</td>
	</tr>
<?php

	html_end_box();

	$display_text = array(
		'NO' => array(
			'display' => __('NO'),
			'align' => 'left',
			'sort' => 'ASC',
			'tip' => __('The name by which this Device will be referred to.')
		),
		'UserCreate' => array(
			'display' => __('UserCreate'),
			'align' => 'left',
			'sort' => 'ASC',
			'tip' => __('Either an IP address, or hostname.  If a hostname, it must be resolvable by either DNS, or from your hosts file.')
		),
		'CL_Number' => array(
			'display' => __('CL_Number'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'nosort1' => array(
			'display' => __('Class Map'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'NameCustomer' => array(
			'display' => __('Tên khách hàng'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'Site' => array(
			'display' => __('Site'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'StatusCustomer' => array(
			'display' => __('StatusCustomer'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'SNMP' => array(
			'display' => __(''),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'Active' => array(
			'display' => __('Tác vụ'),
			'align' => 'right',
			'sort' => 'ASC',
			'tip' => __('The internal database ID for this Device.  Useful when performing automation or debugging.')
		)
	);
	$display_text = api_plugin_hook_function('device_display_text', $display_text);
	if (get_request_var('rows') == -1) {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}
	$hosts = get_Customer_records($total_rows, $rows);
	$nav = html_nav_bar('ListCustomer.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text) + 1, __('Devices'), 'page', 'main');
	form_start('ListCustomer.php', 'chk');
	print $nav;
	html_start_box('', '100%', '', '3', 'center', '');
	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);
	if (cacti_sizeof($hosts)) {
		foreach ($hosts as $host) {
			form_selectable_cell(filter_value($host['cl_number'], get_request_var('filter')), $host['cl_number']);
			form_selectable_cell(filter_value($host['user_create'], get_request_var('filter')), $host['cl_number']);
			// form_selectable_cell(filter_value($host['cl_number'], get_request_var('filter')), $host['cl_number']);
			form_selectable_cell(filter_value($host['cl_number'], get_request_var('filter'), 'graph_view.php?action=preview_Customer&cl_number=' . $host['cl_number']), $host['cl_number']);
			echo "<td style=\"cursor:pointer;\"> 
				<a href='graph_view.php?action=preview_classMap&cl_number={$host['cl_number']}'>Class Map</a>
                </td>";
			form_selectable_cell(filter_value($host['cl_number'], get_request_var('filter')), $host['cl_number']);
			form_selectable_cell(filter_value($host['cl_number'], get_request_var('filter')), $host['cl_number']);
			form_selectable_cell(filter_value($host['cl_number'], get_request_var('filter')), $host['cl_number']);
			//form_selectable_cell(filter_value($host[''], get_request_var('filter')), $host['company_sn']);
			//form_selectable_cell(filter_value($host[''], get_request_var('filter')), $host['company_sn'], '', 'right');			
			form_end_row();
		}
	} else {
		print "<tr class='tableRow'><td colspan='" . (cacti_sizeof($display_text) + 1) . "'><em>" . __('No Customer Found') . "</em></td></tr>";
	}
	html_end_box(false);
	if (cacti_sizeof($hosts)) {
		print $nav;
	}
	form_end();
}
