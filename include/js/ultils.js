/*Date format*/

function getDateNow(dayAdd = 0) {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate() + dayAdd).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function formattedDate(stringDate) {
    const dateObj = new Date(stringDate)

    let result = dateObj.getDate().toString().padStart(2, '0') + "-" +
        (dateObj.getMonth() + 1).toString().padStart(2, '0') + "-" +
        dateObj.getFullYear() + " " +
        dateObj.getHours().toString().padStart(2, '0') + ":" +
        dateObj.getMinutes().toString().padStart(2, '0') + ":" +
        dateObj.getSeconds().toString().padStart(2, '0')
    return result
}

function toDmy(stringYmd) {
    const date = new Date(stringYmd);

    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');

    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    const year = date.getFullYear();

    return `${hours}:${minutes} ${day}-${month}-${year}`;
}

function convertDatetimeSql(dateString) {
    let formattedDate = new Date(dateString);

    let sqlFormattedDate = formattedDate.getFullYear() + "-" +
        ("0" + (formattedDate.getMonth() + 1)).slice(-2) + "-" +
        ("0" + formattedDate.getDate()).slice(-2) + " " +
        ("0" + formattedDate.getHours()).slice(-2) + ":" +
        ("0" + formattedDate.getMinutes()).slice(-2) + ":" +
        ("0" + formattedDate.getSeconds()).slice(-2);

    return sqlFormattedDate
}

function isoDateFormatString(isoDate) {
    const date = new Date(isoDate);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0'); // Months are zero-indexed
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
}

/*Graph points*/
function arrPoint(length, points) {
    let result = new Array()
    const spaces = points - 1

    const stepSize = Math.round(length / spaces)

    for (let i = 0; i < spaces; i++) {
        result.push(i * stepSize)
    }
    result.push(length - 1)

    return result
}

/*local storage*/
function setItemWithExpiry(key, value, ttl) {
    const now = new Date();

    const item = {
        value: value,
        expiry: now.getTime() + ttl,
    };
    localStorage.setItem(key, JSON.stringify(item));
}

function getItemWithExpiry(key) {
    const itemStr = localStorage.getItem(key);

    if (!itemStr) {
        return null;
    }
    
    const item = JSON.parse(itemStr);
    const now = new Date();
    
    if (now.getTime() > item.expiry) {
        localStorage.removeItem(key);
        return null;
    }
    
    return item.value;
}