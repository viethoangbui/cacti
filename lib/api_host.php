<?php
/*-----------------
    function host
 --------------------*/

function get_suppliers()
{
	$noneItem = ['id' => 'None', 'name' => 'None'];
	$suppliers = db_fetch_assoc(
		'SELECT name, id
				FROM suppliers
				WHERE is_enable = 1'
	);

	array_unshift($suppliers, $noneItem);
	echo json_encode($suppliers);
}

function get_site()
{
	$sites = db_fetch_assoc(
		'SELECT name, id
				FROM sites'
	);

	echo json_encode($sites);
}

function host_update()
{
	try {
		$req = $_POST;
		[
			'supplier_id' => $supplierId,
			'description' => $description,
			'snmp_community' => $snmp_community,
			'login' => $login,
			'en_level' => $en_level,
			'loaithietbi' => $loaithietbi,
			'site_id' => $site_id,
			'hostname' => $hostname,
			'password' => $password,
			'config_host' => $config_host,
			'bk_method' => $bk_method,
			'status' => $status,
			'backup' => $backup,
			'authen' => $authen,
			'model' => $model,
			'id' => $id
		] = $req;

		$passwordHash = db_fetch_cell_prepared('SELECT password FROM host WHERE id = ?', array($id));
		$password = convertStrPreventXss($password);
	
		if (
			$authen === 'local' 
			&&$password !== $passwordHash 
			&& $password !== ''
			&& $password !== 'password'
		) {
			$password = password_hash($password, PASSWORD_DEFAULT);
		}

		if($authen === 'tacacs'){
			$password = '';
		}

		$supplierId = strtolower($supplierId) === 'none' ? null :$supplierId;
		$loaithietbi = strtolower($loaithietbi) === 'none' ? null :$loaithietbi;
		$model = strtolower($model) === 'none' ? null :$model;

		db_execute_prepared(
			'UPDATE host
                    SET supplier_id = ?, 
                    description = ?,
                    snmp_community = ?,
                    login = ?,
                    en_level = ?,
                    loaithietbi = ?,
                    site_id = ?,
                    hostname = ?,
                    password = ?,
					config = ?,
					bk_method = ?,
					device_status = ?,
					is_backup = ?,
					authen = ?,
					model = ?
                    WHERE id = ?',
			array(
				convertStrPreventXss($supplierId),
				convertStrPreventXss($description),
				convertStrPreventXss($snmp_community),
				convertStrPreventXss($login),
				convertStrPreventXss($en_level),
				convertStrPreventXss($loaithietbi),
				convertStrPreventXss($site_id),
				convertStrPreventXss($hostname),
				$password,
				convertStrPreventXss($config_host),
				convertStrPreventXss($bk_method),
				convertStrPreventXss($status),
				convertStrPreventXss($backup),
				convertStrPreventXss($authen),
				convertStrPreventXss($model),
				$id
			)
		);
		$_SESSION['modal_success'] = true;

		http_response_code(200);
		echo json_encode(['message' => 'successfully']);
	} catch (\Throwable $th) {
		throw $th;
		http_response_code(500);
		echo json_encode(['message' => 'failed']);
	}
}


function handleEdit_javascript($hosts)
{
?>
	<script src='./include/js/host-custom.js'></script>
	<script type='text/javascript'>
		const hosts = <?= json_encode($hosts) ?>;
		let hostIdGlobal = null

		$(document).ready(function() {
			document.querySelectorAll("#host2_child tr").forEach(row => {
				row.addEventListener("click", function() {

					row.classList.toggle("selected");
				});
			});

			$('.modal-host-edit-toggle').on('click', function(e) {
				e.preventDefault();
				$('.modal-host-edit').toggleClass('is-visible');
			});

			$('.host-editor').on('click', (event) => {
				$('.modal-host-edit').toggleClass('is-visible');
				let host = hosts.find((item) => item.id == event.currentTarget.id)
				hostId = event.currentTarget.id

				if (host !== null &&
					typeof host === 'object' &&
					Object.keys(host).length > 0
				) {
					fetchUrlData(
						`${urlPath}host.php?action=ajax_supplier`,
						'#supplier',
						host.supplier_id,
					)

					fetchUrlData(
						`${urlPath}host.php?action=ajax_device_type&supplier_id=${host?.supplier_id}`,
						'#device-type',
						host.loaithietbi,
					)

					fetchUrlData(
						`${urlPath}host.php?action=ajax_model&device_type_id=${host?.loaithietbi}`,
						'#model',
						host.model,
					)

					fetchUrlData(
						`${urlPath}host.php?action=ajax_site`,
						'#site',
						host.site_id,
					)

					$('#description').val(host.description)
					$('#ip').val(host.hostname)
					$('#community').val(host.snmp_community)
					$('#bk_method').val(host.bk_method ? host.bk_method : 'ssh').selectmenu("refresh")
					$('#status').val(host.status ? host.status : 'force').selectmenu("refresh")
					$('#login').val(host.login)
					$('#en_level').val(host.en_level)
					$('#config_host').val(host.config).selectmenu("refresh")

					$('#authen').val(!host.authen ? 'tacacs' : host.authen).selectmenu("refresh")
					$('#backup').val(host.is_backup).selectmenu("refresh")
					$('#password').val(host.password ? 'password' : '')
					$('#repeat-password').val(host.password ? 'password' : '')
				}
			})

			$('#host-form-update').on('submit', function(e) {
				e.preventDefault()
				const formData = {};

				$.each($(this).serializeArray(), function(_, field) {
					formData[field.name] = field.value;
				});

				if (formData['password'] !== formData['re-password']) {
					$('#password, #repeat-password').addClass('ui-state-error');
					sessionMessage = {
						message: '<?php print __esc("Password and Re password doesn't match"); ?>',
						level: MESSAGE_LEVEL_ERROR
					};
					displayMessages()
					return
				} else {
					$('#password, #repeat-password').removeClass('ui-state-error');
				}

				$.post(`${urlPath}host.php?action=host_update`, {
					...formData,
					__csrf_magic: csrfMagicToken,
					id: hostId
				}).done(function(data) {
					location.reload()
				})
			})

			$('#supplier').on('change', (e) => {
				if (e.target.value === 'None') {
					$('#device-type').selectmenu()
					$('#device-type').empty()
					$('#device-type').append(new Option('None', 'none'))
					$('#device-type').selectmenu("refresh")

					$('#model').selectmenu()
					$('#model').empty()
					$('#model').append(new Option('None', 'none'))
					$('#model').selectmenu("refresh")
					return
				}
				fetchUrlData(
					`${urlPath}host.php?action=ajax_device_type&supplier_id=${e.target.value}`,
					'#device-type'
				)
			})

			$('#device-type').on('change', (e) => {
				if (e.target.value === 'None') {
					$('#model').selectmenu()
					$('#model').empty()
					$('#model').append(new Option('None', 'none'))
					$('#model').selectmenu("refresh")
					return
				}
				fetchUrlData(
					`${urlPath}host.php?action=ajax_model&device_type_id=${e.target.value}`,
					'#model'
				)
			})

			$('#password, #repeat-password').on('change keyup', () => {
				if ($('#password').val() === $('#repeat-password').val()) {
					$('#password, #repeat-password').removeClass('ui-state-error');
					$('.btn-update-submit').removeClass('ui-button-disabled ui-state-disabled');
					$('.btn-update-submit').prop('disabled', false)
				}
			});
		})
	</script>
<?php
}


function renderModal()
{
	?>
	<div class="modal-host-edit">
		<div class="modal-host-edit-overlay modal-host-edit-toggle"></div>
		<div class="modal-host-edit-wrapper modal-host-edit-transition">
			<div class="modal-host-edit-header">
				<button class="modal-host-edit-close modal-host-edit-toggle">
					<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#EE0033">
						<path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z" />
					</svg>
				</button>
				<h2 class="modal-host-edit-heading">Edit device</h2>
			</div>

			<div class="modal-host-edit-body">
				<div class="modal-host-edit-content">
					<form id="host-form-update">
						<section class="host-edit-form">
							<div class="he-left">
								<div class="he-field">
									<label for="supplier" style="color:#EE0033">supplier</label>
									<select id="supplier" name="supplier_id"></select>
								</div>

								<div class="he-field">
									<label for="model" style="color:#EE0033">model</label>
									<select id="model" name="model"></select>
								</div>

								<div class="he-field">
									<label for="description">description</label>
									<input id="description" name="description" type="text" />
								</div>

								<div class="he-field">
									<label for="community">community</label>
									<input id="community" name="snmp_community" type="text" />
								</div>

								<div class="he-field">
									<label for="bk_method">Bk method</label>
									<select id="bk_method" name="bk_method">
										<option value="ssh">SSH</option>
										<option value="telnet">TALNET</option>
									</select>
								</div>

								<div class="he-field">
									<label for="status">Status</label>
									<select id="status" name="status">
										<option value="force">Hiệu lực</option>
										<option value="force_not">Hết hiệu lực</option>
									</select>
								</div>

								<div class="he-field">
									<label for="login">Login</label>
									<input id="login" name="login" type="text" />
								</div>

								<div class="he-field">
									<label for="en_level">en level</label>
									<input id="en_level" name="en_level" type="text" />
								</div>
							</div>

							<div class="he-right">
								<div class="he-field">
									<label for="device-type" style="color:#EE0033">Device type</label>
									<select id="device-type" name="loaithietbi"></select>
								</div>

								<div class="he-field">
									<label for="site" style="color:#EE0033">Site</label>
									<select id="site" name="site_id"></select>
								</div>

								<div class="he-field">
									<label for="ip">Ip device</label>
									<input id="ip" name="hostname" type="text" />
								</div>

								<div class="he-field">
									<label for="config_host">Config</label>
									<select id="config_host" name="config_host">
										<option value="yes">Yes</option>
										<option value="no">No</option>
									</select>
								</div>

								<div class="he-field">
									<label for="authen">Authen</label>
									<select id="authen" name="authen">
										<option value="local">LOCAL</option>
										<option value="tacacs">TACACS</option>
									</select>
								</div>

								<div class="he-field">
									<label for="backup">backup</label>
									<select id="backup" name="backup">
										<option value="1">YES</option>
										<option value="0">NO</option>
									</select>
								</div>

								<div class="he-field password-field">
									<label for="password">password</label>
									<input id="password" name="password" type="password" />
								</div>

								<div class="he-field password-field">
									<label for="repeat-password">en password</label>
									<input id="repeat-password" name="re-password" type="password" />
								</div>
							</div>
						</section>
						<div>
							<div style="width: fit-content;margin:0 auto;margin-top:8px;">
								<button class="modal-host-edit-toggle btn-update btn-update-discard" style="margin-right: 12px;">
									<svg xmlns="http://www.w3.org/2000/svg" height="30px" viewBox="0 -960 960 960" width="30px" fill="#EE0033">
										<path d="m336-280 144-144 144 144 56-56-144-144 144-144-56-56-144 144-144-144-56 56 144 144-144 144 56 56ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z" />
									</svg>
								</button>
								<button class="btn-update btn-update-submit" type="submit">
									<svg xmlns="http://www.w3.org/2000/svg" height="30px" viewBox="0 -960 960 960" width="30px" fill="#EE0033">
										<path d="M840-680v480q0 33-23.5 56.5T760-120H200q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h480l160 160Zm-80 34L646-760H200v560h560v-446ZM480-240q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35ZM240-560h360v-160H240v160Zm-40-86v446-560 114Z" />
									</svg>
								</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
<?php
}

?>