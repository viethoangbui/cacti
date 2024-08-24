function fetchData(url, callback) {
    $.get(url)
        .done(function (data) {            
            if (data.charAt(0) !== '[') {
                return;
            }

            const dataArr = JSON.parse(data);
            

            if (typeof dataArr === 'object' && !Array.isArray(dataArr) && dataArr !== null) {
                return;
            }

            callback(dataArr);
        })
        .fail(function (data) {
            getPresentHTTPError(data);
        });
}

function populateSelect(elementId, dataArr, selectedId = null) {
    let selectContent = '';
    if (dataArr?.length !== 0) {
        dataArr.forEach(item => {
            selectContent += `<option value="${item.id}">${item.name}</option>`;
        });
    }

    $(`#${elementId}`).html(selectContent);

    if (selectedId) {
        $(`#${elementId}`).val(selectedId).change();
    }
}

function getDeviceType(e, deviceId = null) {
    const url = `${urlPath}host.php?action=ajax_device_type&supplier_id=${e.target.value}`;
    fetchData(url, (dataArr) => populateSelect('device_type_id', dataArr, deviceId));
}

function getModel(e, modelId = null) {
    const url = `${urlPath}host.php?action=ajax_model&device_type_id=${e.target.value}`;
    fetchData(url, (dataArr) => populateSelect('model_id', dataArr, modelId));
}

function getDetailHost(supplierId, deviceId, modelId) {
    const deviceTypeUrl = `${urlPath}host.php?action=ajax_device_type&supplier_id=${supplierId}`;
    fetchData(deviceTypeUrl, (dataArr) => {
        populateSelect('device_type_id', dataArr, deviceId);
        
        const modelUrl = `${urlPath}host.php?action=ajax_model&device_type_id=${deviceId}`;
        fetchData(modelUrl, (modelDataArr) => populateSelect('model_id', modelDataArr, modelId));
    });
}


$(document).ready(function () {
    $('#device_type_id-button').hide();
    $('#device_type_id').show();
    $('#device_type_id').addClass('ui-selectmenu-button ui-button ui-widget ui-selectmenu-button-open ui-corner-top')
    $('#model_id-button').hide();
    $('#model_id').show();
    $('#model_id').addClass('ui-selectmenu-button ui-button ui-widget ui-selectmenu-button-open ui-corner-top')
});
