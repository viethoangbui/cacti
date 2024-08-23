function getDeviceType(e, deviceId = null){
    $.get(urlPath+`host.php?action=ajax_device_type&supplier_id=${e.target.value}`)
    .done(function(data) {
        if(data.charAt(0) !== '['){
            return
        }
        
        const dataArr = JSON.parse(data)

        if(typeof dataArr === 'object' && !Array.isArray(dataArr) && dataArr !== null){
            return;
        }
        
        if(dataArr?.length !== 0){
            let selectContent = '<option value="0">None</option>';
            dataArr.forEach((item, index) => {
                selectContent+= `<option value="${item.id}">${item.name}</option>`
            })
            
            $('#device_type_id').html(selectContent)
        }else{
            $('#device_type_id').html('<option value="0">None</option>')
        }

        if(deviceId){            
            $('#device_type_id').val(deviceId).change()
        }
    })
    .fail(function(data) {				
        getPresentHTTPError(data);
    });
}

function getModel(e, modelId){        
    $.get(urlPath+`host.php?action=ajax_model&device_type_id=${e.target.value}`)
    .done(function(data) {
        if(data.charAt(0) !== '['){
            return
        }			
        const dataArr = JSON.parse(data)

        if(typeof dataArr === 'object' && !Array.isArray(dataArr) && dataArr !== null){
            return;
        }
        
        if(dataArr?.length !== 0){
            let selectContent = '<option value="0">None</option>';
            dataArr.forEach((item, index) => {
                selectContent+= `<option value="${item.id}">${item.name}</option>`
            })
            
            $('#model_id').html(selectContent)
        }else{
            $('#model_id').html('<option value="0">None</option>')
        }
        
        if(modelId){
            $('#model_id').val(modelId).change()
        }
    })
    .fail(function(data) {				
        getPresentHTTPError(data);
    });
}

$(document).ready(function() {
    $('#device_type_id-button').hide();
    $('#device_type_id').show();
    $('#device_type_id').addClass('ui-selectmenu-button ui-button ui-widget ui-selectmenu-button-open ui-corner-top')
    $('#model_id-button').hide();
    $('#model_id').show();
    $('#model_id').addClass('ui-selectmenu-button ui-button ui-widget ui-selectmenu-button-open ui-corner-top')
});
