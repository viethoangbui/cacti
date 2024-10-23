<?php
require_once('./include/auth.php');

top_header();
?>
<link rel="stylesheet" href="./include/css/supplier.css" />
<?php

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
        <tbody id="tb-body">
            <tr class="tableHeader">
                <th>#</th>
                <th>Cl number</th>
                <th>Download limit</th>
                <th>Upload Limit</th>
                <th>Region Sn</th>
                <th>Create at</th>
                <th>Update at</th>
            </tr>
        </tbody>
    </table>
</section>

<script>
    const drawBodyTable = (data) => {
        let trList = ''

        data.forEach((element, index) => {
            trList += `
                <tr class="odd selectable tableRow">
                    <td class="nowrap">${index + 1}</td>
                    <td class="nowrap">${element.cl_number}</td>
                    <td class="nowrap">${element.download_limit}</td>
                    <td class="nowrap">${element.upload_limit}</td>
                    <td class="nowrap">${element.region_sn}</td>
                    <td class="nowrap">${element.time_create}</td>
                    <td class="nowrap">${element.time_update}</td>
                </tr>
            `
        });

        return trList;
    }

    $(document).ready(function() {

        $.post('https://670656c8a0e04071d2266aea.mockapi.io/api/customer/bandwidth-max/token', {
            username: 'admin',
            password: 'password'
        }).done(function(dataToken) {
            const token = dataToken.token

            $.ajax({
                url: 'https://670656c8a0e04071d2266aea.mockapi.io/api/customer/bandwidth-max/customers',
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                // method: 'GET',
                // data: {
                //     key1: 'value1',
                //     key2: 'value2'
                // },
                success: function(data) {
                    $('#tb-body').append(drawBodyTable(data))
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                }
            });
        });
    })
</script>

<?php
html_end_box();
bottom_footer();
?>