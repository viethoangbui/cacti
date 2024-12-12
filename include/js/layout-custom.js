function handleChangeColumnGraph(e) {
    const amoutColumn = e?.target?.value ? e.target.value : e

    const containerWidth = 90
    const imageWidth = amoutColumn != 1 ? (containerWidth / amoutColumn + '%') : '60%'

    $('#graph-view-custom .graphimage').each((_, element) => {
        let $element = $(element)

        $element.width(imageWidth)
    })
    $('#graph-preview .graphimage').each((_, element) => {
        let $element = $(element)
        $element.width(imageWidth)
    })

    $('#graph-view-custom-sandvine .graphimage').each((_, element) => {
        let $element = $(element)
        $element.width(imageWidth)
    })
}


function handleNewColumnGraph(e) {
    const amoutColumn = e?.target?.value ? e.target.value : e

    const containerWidth = 90
    const imageWidth = amoutColumn != 1 ? (containerWidth / amoutColumn + '%') : '60%'

    $('#graph-view-custom .warpper-graph').each((_, element) => {
        let $element = $(element)

        $element.width(imageWidth)
    })
}

$('.graphimage').each((_, element) => {
    let $element = $(element)
    $element.css({ "border": "1px solid #fff" })
})


function processRrd(table,
    rrd_name_field,
    graph_name_field,
    cl_number,
    columns = 1
) {
    $('#graph-view-custom').empty()

    const params = new URLSearchParams({
        table,
        rrd_name_field,
        graph_name_field,
        cl_number,
    }).toString();

    // Send the GET request with query parameters
    const worker = new Worker('./include/js/fetchWorker.js');

    // Fetch data
    fetch(`graph_view.php?action=graph_json_custom&${params}`, {
        method: 'GET',
    })
        .then(response => {
            if (!response.ok) {
                // Handle HTTP errors
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json(); // Parse the JSON from the response
        })
        .then(data => {
            // Array to store promises for processing elements
            let divLoading = '';
            data.forEach((_element, index) => {
                divLoading += `<div key=${index} class="warpper-graph" style="display:inline-block;padding:2px;width:${columns != 1 ? (90 / columns + '%') : '60%'};min-height:150px;"><div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div></div>`
            });
            $('#graph-view-custom').append(divLoading);
            $('.warpper-graph').addClass('')
            handleNewColumnGraph(columns)
            const promises = data.map(async (element, index) => {
                const params = new URLSearchParams({
                    rrd_file: element.path_graph,
                    rrd_name: element.rrd_name
                }).toString();

                // Send data to the worker
                worker.postMessage({
                    url: `http://localhost/cacti/graph_view.php?action=graph_custom&${params}`,
                    key: index
                });
            });
            // Wait for all promises to resolve
            return Promise.all(promises);
        })
        .then(() => {
            // Listen for messages from the worker
            worker.onmessage = (event) => {
                $(`#graph-view-custom div[key=${event.data.key}]`).html(
                    `<img class="graphimage" loading="lazy" src="${event.data.image}" style="width:100%"/>`
                );
            };

            // Handle worker errors
            worker.onerror = (error) => {
                console.log('Worker Error:', error.message);
            };
        })
        .catch(error => {
            console.log('Error:', error);
        });
}