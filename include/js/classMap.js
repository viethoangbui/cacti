function updateModal(ids, clText = null, status, ipAddress = null) {
    $('input[name=ids]').val(ids)
    $('#cl').val(clText)

    if (Number(status)) {
        $('input[name=status]').attr('checked', 1)
    }

    if (!Number(status)) {
        $('input[name=status]').removeAttr('checked')
    }

    $('.ip-address-selected tbody tr:first-child, .ip-address-selected tbody tr:last-child').empty()
    
    if(ipAddress?.length > 0){
        $('#modal-filed-ip-selected').show()
        ipAddress.forEach((element, index) => {
            if(index %2 === 0){
                $('.ip-address-selected tbody tr:first-child')
                .append(`<td 
                            style="padding:4px;padding-top:0;transform:rotateX(180deg);">
                            <span style="background: #9D9D9D;border-radius:8px;color:white;padding: 3px 3px 5px 3px;display:inline-block;">
                            ${element}</span>
                            </td>`)
            }else{
                $('.ip-address-selected tbody tr:last-child')
                .append(`<td 
                            style="padding:4px;transform:rotateX(180deg);">
                            <span style="background: #9D9D9D;border-radius:8px;color:white;padding: 3px 3px 5px 3px;display:inline-block;">
                            ${element}</span>
                            </td>`)
            }
        });
    }else{
        $('#modal-filed-ip-selected').hide()
    }
}

function saveClassMaps(id, ip) {
    arrSessionStorage.push(id)
    sessionStorage.setItem('class_map_ids', JSON.stringify(arrSessionStorage))

    ipSessionStorage.push(ip)
    sessionStorage.setItem('ips_address', JSON.stringify(ipSessionStorage))
}

function removeClassMap(id, ip) {
    const newArrLocalStorage = arrSessionStorage.filter((value) => value !== id)
    sessionStorage.setItem('class_map_ids', JSON.stringify(newArrLocalStorage))
    arrSessionStorage = [...newArrLocalStorage]

    const newArrIpLocalStorage = ipSessionStorage.filter((value) => value !== ip)
    sessionStorage.setItem('ips_address', JSON.stringify(newArrIpLocalStorage))
    ipSessionStorage = [...newArrIpLocalStorage]
}

function enableEditButton() {
    if (arrSessionStorage.length > 0)
        $('#btn-edit-classmap').show()
}