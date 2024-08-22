<?php
$guest_account = true;
include('./include/auth.php');
include_once('./lib/rrd.php');

/* set default action */
set_default_action('view');

if (!isset_request_var('view_type')) {
	set_request_var('view_type', '');
}

/* ================= input validation ================= */
get_filter_request_var('rra_id', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^([0-9]+|all)$/')));
get_filter_request_var('local_graph_id');
get_filter_request_var('graph_end');
get_filter_request_var('graph_start');
get_filter_request_var('view_type', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^([a-zA-Z0-9]+)$/')));
/* ==================================================== */

api_plugin_hook_function('graph');

include_once('./lib/html_tree.php');

top_graph_header();

if (!isset_request_var('rra_id')) {
	set_request_var('rra_id', 'all');
}

if (get_request_var('rra_id') == 'all' || isempty_request_var('rra_id')) {
	$sql_where = ' AND dspr.id IS NOT NULL';
} else {
	$sql_where = ' AND dspr.id=' . get_request_var('rra_id');
}

// $exists = db_fetch_cell_prepared('SELECT local_graph_id
// 	FROM graph_templates_graph
// 	WHERE local_graph_id = ?',
// 	array(get_request_var('local_graph_id')));

// /* make sure the graph requested exists (sanity) */
// if (!$exists) {
// 	print '<strong><font class="txtErrorTextBox">' . __('GRAPH DOES NOT EXIST') . '</font></strong>';
// 	bottom_footer();
// 	exit;
// }

/* take graph permissions into account here */
if (!is_graph_allowed(get_request_var('local_graph_id'))) {
	header('Location: permission_denied.php');
	exit;
}

$graph_title = get_graph_title(get_request_var('local_graph_id'));

if (get_request_var('action') != 'properties') {
	print "<table width='100%' class='cactiTable'>";
}

$rras = get_associated_rras(get_request_var('local_graph_id'), $sql_where);

switch (get_request_var('action')) {
case 'view':
    
	api_plugin_hook_function('page_buttons',
		array(
			'lgid'   => get_request_var('local_graph_id'),
			'leafid' => '',//$leaf_id,
			'mode'   => 'mrtg',
			'rraid'  => get_request_var('rra_id')
		)
	);

	?>
	<tr class='tableHeader'>
		<td colspan='3' class='textHeaderDark'>
			<strong><?php print __('Viewing Graph');?></strong> '<?php print html_escape($graph_title);?>'
		</td>
	</tr>
	<?php

	$graph = db_fetch_row_prepared('SELECT gtg.local_graph_id, width, height, title_cache, gtg.graph_template_id, h.id AS host_id, h.disabled
		FROM graph_templates_graph AS gtg
		INNER JOIN graph_local AS gl
		ON gtg.local_graph_id = gl.id
		LEFT JOIN host AS h
		ON gl.host_id = h.id
		WHERE gtg.local_graph_id = ?',
		array(get_request_var('local_graph_id')));

	$graph_template_id = $graph['graph_template_id'];

	$i = 0;
	if (cacti_sizeof($rras)) {
		$graph_end   = time() - 30;
		foreach ($rras as $rra) {
			if (!empty($rra['timespan'])) {
				$graph_start = $graph_end - $rra['timespan'];
			} else {
				$graph_start = $graph_end - ($rra['step'] * $rra['rows'] * $rra['steps']);
			}

			$aggregate_url = aggregate_build_children_url(get_request_var('local_graph_id'), $graph_start, $graph_end, $rra['id']);

			?>
			<tr class='tableRowGraph'>
				<td class='center'>
					<table class='graphWrapperOuter' data-disabled='<?php print ($graph['disabled'] == 'on' ? 'true':'false');?>'>
						<tr>
							<td>
								<div class='graphWrapper' id='wrapper_<?php print $graph['local_graph_id'] ?>' graph_id='<?php print $graph['local_graph_id'];?>' rra_id='<?php print $rra['id'];?>' graph_width='<?php print $graph['width'];?>' graph_height='<?php print $graph['height'];?>' graph_start='<?php print $graph_start;?>' graph_end='<?php print $graph_end;?>' title_font_size='<?php print ((read_user_setting('custom_fonts') == 'on') ? read_user_setting('title_size') : read_config_option('title_size'));?>'></div>
							</td>

							<?php if (is_realm_allowed(27)) { ?><td id='dd<?php print get_request_var('local_graph_id');?>' style='vertical-align:top;' class='graphDrillDown noprint'>
								<a class='iconLink utils' href='#' id='graph_<?php print get_request_var('local_graph_id');?>_util' graph_start='<?php print $graph_start;?>' graph_end='<?php print $graph_end;?>' rra_id='<?php print $rra['id'];?>'><img class='drillDown' src='<?php print $config['url_path'] . 'images/cog.png';?>' alt='' title='<?php print __esc('Graph Details, Zooming and Debugging Utilities');?>'></a><br>
								<a id='graph_<?php print $rra['id'];?>_csv' class='iconLink csv' href='<?php print html_escape($config['url_path'] . 'graph_xport.php?local_graph_id=' . get_request_var('local_graph_id') . '&rra_id=' . $rra['id'] . '&view_type=' . get_request_var('view_type') .  '&graph_start=' . $graph_start . '&graph_end=' . $graph_end);?>'><img src='<?php print $config['url_path'] . 'images/table_go.png';?>' alt='' title='<?php print __esc('CSV Export');?>'></a><br>

								<?php
								if (is_realm_allowed(10) && $graph_template_id > 0) {
									print "<a class='iconLink' role='link' title='" . __esc('Edit Graph Template') . "' href='" . html_escape($config['url_path'] . 'graph_templates.php?action=template_edit&id=' . $graph_template_id) . "'><img src='" . html_escape($config['url_path'] . 'images/template_edit.png') . "'></img></a>";
									print '<br/>';
								}

								if (read_config_option('realtime_enabled') == 'on' || is_realm_allowed(25)) {
									print "<a class='iconLink' href='#' onclick=\"window.open('".$config['url_path'] . 'graph_realtime.php?top=0&left=0&local_graph_id=' . get_request_var('local_graph_id') . "', 'popup_" . get_request_var('local_graph_id') . "', 'directories=no,toolbar=no,menubar=no,resizable=yes,location=no,scrollbars=no,status=no,titlebar=no,width=650,height=300');return false\"><img src='" . $config['url_path'] . "images/chart_curve_go.png' alt='' title='" . __esc('Click to view just this Graph in Real-time') . "'></a><br/>\n";
								}

								print ($aggregate_url != '' ? $aggregate_url:'');

								api_plugin_hook('graph_buttons', array('hook' => 'view', 'local_graph_id' => get_request_var('local_graph_id'), 'rra' => $rra['id'], 'view_type' => get_request_var('view_type')));

								?>
							</td><?php } ?>
						</tr>
						<tr>
							<td class='no-print center'>
								<span><?php print html_escape($rra['name']);?></span>
							</td>
						</tr>
					</table>
					<input type='hidden' id='thumbnails' value='<?php print html_escape(get_request_var('thumbnails'));?>'></input>
				</td>
			</tr>
			<?php
			$i++;
		}

		api_plugin_hook_function('tree_view_page_end');
	}

	?>
	<script type='text/javascript'>

	var originalWidth = null;
	var refreshTime   = <?php print read_user_setting('page_refresh')*1000;?>;
	var graphTimeout  = null;

	function initializeGraph() {
		$('a.iconLink').tooltip();

		$('.graphWrapper').each(function() {
			var itemWrapper = $(this);
			var itemGraph   = $(this).find('.graphimage');

			if (itemGraph.length != 1) {
				itemGraph = itemWrapper;
			}

			graph_id     = itemGraph.attr('graph_id');
			rra_id       = itemGraph.attr('rra_id');
			graph_height = itemGraph.attr('graph_height');
			graph_width  = itemGraph.attr('graph_width');
			graph_start  = itemGraph.attr('graph_start');
			graph_end    = itemGraph.attr('graph_end');

			$.getJSON(urlPath+'graph_json.php?'+
				'local_graph_id='+graph_id+
				'&graph_height='+graph_height+
				'&graph_start='+graph_start+
				'&graph_end='+graph_end+
				'&rra_id='+rra_id+
				'&graph_width='+graph_width+
				'&disable_cache=true'+
				($('#thumbnails').val() == 'true' ? '&graph_nolegend=true':''))
				.done(function(data) {
					wrapper=$('#wrapper_'+data.local_graph_id+'[rra_id=\''+data.rra_id+'\']');
					wrapper.html(
						"<img class='graphimage' id='graph_"+data.local_graph_id+
						"' src='data:image/"+data.type+";base64,"+data.image+
						"' rra_id='"+data.rra_id+
						"' graph_type='"+data.type+
						"' graph_id='"+data.local_graph_id+
						"' graph_start='"+data.graph_start+
						"' graph_end='"+data.graph_end+
						"' graph_left='"+data.graph_left+
						"' graph_top='"+data.graph_top+
						"' graph_width='"+data.graph_width+
						"' graph_height='"+data.graph_height+
						"' image_width='"+data.image_width+
						"' image_height='"+data.image_height+
						"' canvas_left='"+data.graph_left+
						"' canvas_top='"+data.graph_top+
						"' canvas_width='"+data.graph_width+
						"' canvas_height='"+data.graph_height+
						"' width='"+data.image_width+
						"' height='"+data.image_height+
						"' value_min='"+data.value_min+
						"' value_max='"+data.value_max+"'>"
					);

					$('#graph_start').val(data.graph_start);
					$('#graph_end').val(data.graph_end);

					var gr_location = '#graph_'+data.local_graph_id;
					if (data.rra_id > 0) {
						gr_location += '[rra_id=\'' + data.rra_id + '\']';
					}

					$(gr_location).zoom({
						inputfieldStartTime : 'date1',
						inputfieldEndTime : 'date2',
						serverTimeOffset : <?php print date('Z');?>
					});

					responsiveResizeGraphs(true);
				})
				.fail(function(data) {
					getPresentHTTPError(data);
				});
		});

		$('a[id$="_util"]').off('click').on('click', function() {
			graph_id    = $(this).attr('id').replace('graph_','').replace('_util','');
			rra_id      = $(this).attr('rra_id');
			graph_start = $(this).attr('graph_start');
			graph_end   = $(this).attr('graph_end');

			$.get(urlPath+'graph.php?' +
				'action=zoom' +
				'&header=false' +
				'&local_graph_id='+graph_id+
				'&rra_id='+rra_id+
				'&graph_start='+graph_start+
				'&graph_end='+graph_end)
				.done(function(data) {
					$('#main').html(data);
					$('#breadcrumbs').append('<li><a id="nav_util" href="#"><?php print __('Utility View');?></a></li>');
					applySkin();
				})
				.fail(function(data) {
					getPresentHTTPError(data);
				});
		});
		$('a[id$="_csv"]').each(function() {
			$(this).off('click').on('click', function(event) {
				event.preventDefault();
				event.stopPropagation();
				document.location = $(this).attr('href');
				Pace.stop();
			});
		});
		graphTimeout = setTimeout(initializeGraph, refreshTime);
	}

	$(function() {
		pageAction = 'graph';

		if (graphTimeout !== null) {
			clearTimeout(graphTimeout);
		}

		initializeGraph();
		$('#navigation').show();
		$('#navigation_right').show();
	});
	</script>
	<?php
	break;
}



print '</table>';

bottom_footer();

