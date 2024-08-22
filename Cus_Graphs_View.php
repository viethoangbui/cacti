<?php

$guest_account = true;

include('./include/auth.php');
include_once('./lib/html_tree.php');
include_once('./lib/html_graph.php');
include_once('./lib/api_tree.php');
include_once('./lib/graphs.php');
include_once('./lib/reports.php');
include_once('./lib/timespan_settings.php');

/* set the default graph action */
set_default_graph_action();

/* perform spikekill action */
html_spikekill_actions();


/* setup realtime defaults if they are not set */
initialize_realtime_step_and_window();

switch (get_nfilter_request_var('action')) {
case 'update_timespan':
	$_SESSION['sess_current_date1'] = get_request_var('date1');
	$_SESSION['sess_current_date2'] = get_request_var('date2');

	break;
case 'preview':
	if (!is_view_allowed('show_preview')) {
		print "<font class='txtErrorTextBox'>" . __('YOU DO NOT HAVE RIGHTS FOR PREVIEW VIEW') . "</font>";
		bottom_footer();
		exit;
	}

	if (isset_request_var('external_id')) {
		$host_id = db_fetch_cell_prepared('SELECT id FROM host WHERE external_id = ?', array(get_nfilter_request_var('external_id')));
		if (!empty($host_id)) {
			set_request_var('host_id', $host_id);
			set_request_var('reset',true);
		}
	}

	html_graph_validate_preview_request_vars();

	top_graph_header();

	/* include graph view filter selector */
	$graph_title ='Graph Preview Filters';
	if (!isempty_request_var('cl_number')){
		$graph_title = db_fetch_cell_prepared('select CONCAT(cl_number, " - ", cus_name) AS graph_title from customer WHERE cl_number =  ?',
		array(get_request_var('cl_number')));
	}
	
	html_start_box(__($graph_title) . (isset_request_var('style') && get_request_var('style') != '' ? ' ' . __('[ Custom Graph List Applied - Filtering from List ]'):''), '100%', '', '3', 'center', '');

	html_graph_preview_filter('Cus_Graphs_View.php', 'preview');

	html_end_box();

	/* the user select a bunch of graphs of the 'list' view and wants them displayed here */
	$sql_or = '';
	if (isset_request_var('style')) {
		if (get_request_var('style') == 'selective') {
			$graph_list = array();

			/* process selected graphs */
			if (!isempty_request_var('graph_list')) {
				foreach (explode(',', get_request_var('graph_list')) as $item) {
					if (is_numeric($item)) {
						$graph_list[$item] = 1;
					}
				}
			}
			if (!isempty_request_var('graph_add')) {
				foreach (explode(',', get_request_var('graph_add')) as $item) {
					if (is_numeric($item)) {
						$graph_list[$item] = 1;
					}
				}
			}
			/* remove items */
			if (!isempty_request_var('graph_remove')) {
				foreach (explode(',', get_request_var('graph_remove')) as $item) {
					unset($graph_list[$item]);
				}
			}

			$i = 0;
			foreach ($graph_list as $item => $value) {
				$graph_array[$i] = $item;
				$i++;
			}

			if ((isset($graph_array)) && (cacti_sizeof($graph_array) > 0)) {
				/* build sql string including each graph the user checked */
				$sql_or = array_to_sql_or($graph_array, 'gtg.local_graph_id');
			}
		}
	}

	$total_graphs = 0;

	/* create filter for sql */
	$sql_where  = '';
	if (!isempty_request_var('rfilter')) {
		$sql_where .= " gtg.title_cache RLIKE '" . get_request_var('rfilter') . "'";
	}

	$sql_where .= ($sql_or != '' && $sql_where != '' ? ' AND ':'') . $sql_or;

	if (!isempty_request_var('host_id') && get_request_var('host_id') > 0) {
		$sql_where .= (empty($sql_where) ? '' : ' AND') . ' gl.host_id=' . get_request_var('host_id');
	} elseif (isempty_request_var('host_id')) {
		$sql_where .= (empty($sql_where) ? '' : ' AND') . ' gl.host_id=0';
	}

	if (!isempty_request_var('graph_template_id') && get_request_var('graph_template_id') != '-1' && get_request_var('graph_template_id') != '0') {
		$sql_where .= ($sql_where != '' ? ' AND ':'') . ' (gl.graph_template_id IN (' . get_request_var('graph_template_id') . '))';
	} elseif (get_request_var('graph_template_id') == '0') {
		$sql_where .= ($sql_where != '' ? ' AND ':'') . ' (gl.graph_template_id IN (' . get_request_var('graph_template_id') . '))';
	}
	if (!isempty_request_var('cl_number')) {
		$sql_where .= (empty($sql_where) ? '' : ' AND') . ' gl.cl_number=' . get_request_var('cl_number');
	}
	$limit      = (get_request_var('graphs')*(get_request_var('page')-1)) . ',' . get_request_var('graphs');
	$order      = 'gtg.title_cache';

	$graphs     = get_allowed_graphs($sql_where, $order, $limit, $total_graphs);

	$nav = html_nav_bar('Cus_Graphs_View.php', MAX_DISPLAY_PAGES, get_request_var('page'), get_request_var('graphs'), $total_graphs, get_request_var('columns'), __('Graphs'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	if (get_request_var('thumbnails') == 'true') {
		html_graph_thumbnail_area($graphs, '', 'graph_start=' . get_current_graph_start() . '&graph_end=' . get_current_graph_end(), '', get_request_var('columns'));
	} else {
		html_graph_area($graphs, '', 'graph_start=' . get_current_graph_start() . '&graph_end=' . get_current_graph_end(), '', get_request_var('columns'));
	}

	html_end_box();

	if ($total_graphs) {
		print $nav;
	}

	if (!isset_request_var('header') || get_nfilter_request_var('header') == 'false') {
		bottom_footer();
	}

	break;
}

