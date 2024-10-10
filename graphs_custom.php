<?php
require_once('./include/auth.php');

top_header();

if (!isset($_GET['cl_number'])) {
    header('Location:index.php');
}

?>
<link href="./include/css/graph.css" rel="stylesheet" />
<script src="./include/js/chart.js" type="text/javascript"></script>

<div id="graphs-custom-area" class="mt-3">
    <div id="graphs-custom-filter">
        <div class="me-3 d-flex">
            <label for="from-date">
                From</label>
            <input id="from-date" type="datetime-local" name="from-date" class="form-control" />
        </div>

        <div class="d-flex">
            <label for="to-date">End</label>
            <input id="to-date" type="datetime-local" name="to-date" class="form-control" />
            <button type="button" class="btn-custom ms-1">search</button>
        </div>
    </div>

    <section>
        <div
            id="graph-spinner"
            class="spinner-grow text-success" style="width: 3rem; height: 3rem; position:absolute; top: 50%; right: 50%;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <canvas id="myChart" class="m-3"></canvas>
        <div class="legend-note hidden">
            <div class="legend-row">
                <div class="legend-box legend-box-in">
                </div>
                <div class="legend-title">
                    <span>Inbound
                    </span>
                    <p>
                        <span>Current:
                        </span>
                        <span id="in-curr" class="unit-m"></span>
                    </p>

                    <p>
                        <span>Average:
                        </span>
                        <span id="in-aver" class="unit-m"></span>
                    </p>

                    <p>
                        <span>Maxium:
                        </span>
                        <span id="in-max" class="unit-m"></span>
                    </p>
                </div>
            </div>

            <div class="legend-row">
                <div class="legend-box legend-box-out">
                </div>
                <div class="legend-title">
                    <span>Outbound
                    </span>
                    <p>
                        <span>Current:
                        </span>
                        <span id="out-curr" class="unit-m"></span>
                    </p>

                    <p>
                        <span>Average:
                        </span>
                        <span id="out-aver" class="unit-m"></span>
                    </p>

                    <p>
                        <span>Maxium:
                        </span>
                        <span id="out-max" class="unit-m"></span>
                    </p>
                </div>
            </div>

            <div class="legend-row">
                <div class="legend-box legend-box-95">
                </div>
                <div class="legend-title">
                    <span>95th Percentile
                    </span>
                    <span id="95th"></span>
                </div>
            </div>
        </div>
    </section>
</div>
<script src="./include/js/ultils.js"></script>
<script>
    const ctx = $('#myChart')
    let config = {}
    const chart = new Chart(ctx, config)

    async function checkExpiry(){
        if(!localStorage.getItem('access_token')){
            await getAccessToken()
        }
    }

    function getAccessToken() {
        return new Promise((resolve, reject) => {
            $.ajax({
                type: "POST",
                url: '/accestoken',
                data: JSON.stringify({
                    username: 'user',
                    password: 'user'
                }),
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                success: function(response) {
                    setItemWithExpiry('access_token', response.access_token, 1200000)
                    resolve('success');
                },
                error: (err) => {
                    console.error('Error fetching access token:', err);
                    reject(err);
                }
            })
        })
    }

    function getData(fromDate, toDate) {
        $.ajax({
            type: "POST",
            url: '/graph',
            data: JSON.stringify({
                from_date: isoDateFormatString(fromDate),
                to_date: isoDateFormatString(toDate),
                customer: '<?= $_GET['cl_number'] ?>',
            }),
            headers: {
                'Authorization': `Bearer ${getItemWithExpiry('access_token')}`,
                'Content-Type': 'application/json'
            },
            success: function(response) {
                const bwdIns = []
                const bwdOuts = []
                const labels = []
                const th95Per = []

                response.dataResult.forEach((item) => {
                    bwdIns.push(item.bwd_in)
                    bwdOuts.push(item.bwd_out)
                    labels.push(formattedDate(item.time_monitor))
                    th95Per.push(response.dataResult[0].percentage_95)
                })

                const points = arrPoint(labels.length, 4)
                $('#graph-spinner').hide()

                chart.data = {
                    labels: labels,
                    datasets: [{
                            type: 'bar',
                            label: 'Inbound',
                            data: bwdIns,
                            borderColor: '#00FF00',
                            backgroundColor: '#00FF00',
                            fill: true,
                            order: 3,
                            borderWidth: 0,
                            barPercentage: 1,
                            categoryPercentage: 1
                        },
                        {
                            type: 'line',
                            label: 'Outbound',
                            data: bwdOuts,
                            borderColor: '#0000FF',
                            backgroundColor: '#0000FF',
                            order: 2,
                            borderWidth: 2
                        },
                        {
                            type: 'line',
                            data: th95Per,
                            borderColor: '#f54e42',
                            backgroundColor: '#f54e42',
                            order: 1,
                            borderWidth: 2
                        },
                    ]
                }

                chart.options = {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    stacked: false,
                    plugins: {
                        title: {
                            display: true,
                            text: `From     ${toDmy($('input[name=from-date]').val())}    To    ${toDmy($('input[name=to-date]').val())}`,
                            position: 'bottom',
                            font: {
                                size: 15
                            }
                        },
                        subtitle: {
                            display: true,
                            text: 'Test',
                            font: {
                                size: 20,
                                family: 'Verdana, Arial, Helvetica, sans-serif,Bold'
                            },
                            position: 'top'
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            autoSkip: false,
                            ticks: {
                                stepSize: 0.5,
                                callback: function(value, index, objects) {
                                    if (value % 2 === 0 || objects.length === (index + 1)) {
                                        return value
                                    }
                                }
                            },
                        },
                        x: {
                            ticks: {
                                stepSize: 1,
                                autoSkip: false,
                                callback: function(_value, index) {
                                    if (
                                        points.includes(index)
                                    ) {
                                        return labels[index]
                                    }
                                },
                                align: 'center',
                                maxRotation: points.length >= 10 ? 90 : 0,
                                minRotation: points.length >= 10 ? 45 : 0,
                            },
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                                color: 'transparent'
                            },
                            ticks: {
                                callback: function() {
                                    return ''
                                }
                            },
                        }
                    },
                    elements: {
                        point: {
                            radius: 0
                        }
                    },
                }
                chart.update()

                $('#in-curr').text(Number(dataRes.bwd_in_current).toFixed(2))
                $('#in-aver').text(Number(dataRes.bwd_in_average).toFixed(2))
                $('#in-max').text(Number(dataRes.bwd_in_max).toFixed(2))

                $('#out-curr').text(Number(dataRes.bwd_out_current).toFixed(2))
                $('#out-aver').text(Number(dataRes.bwd_out_average).toFixed(2))
                $('#out-max').text(Number(dataRes.bwd_out_max).toFixed(2))
                $('#95th').text(`(${dataRes.dataResult[0].percentage_95} M)`)

                $('.legend-note').removeClass('hidden')
                $('#graphs-custom-filter').removeClass('hidden')
            },
            error: (err) => {
                throw err
            }
        })
    }

    $(document).ready(function() {
        const fromDate = getDateNow(-2)
        const toDate = getDateNow()
        $('input[name=from-date]').val(fromDate)
        $('input[name=to-date]').val(toDate)

        getAccessToken().then(() => {
            getData(fromDate, toDate)
        })

        $('.btn-custom').on('click', () => {
            checkExpiry().then(() => {
                getData(
                    $('input[name=from-date]').val(),
                    $('input[name=from-date]').val()
                )
            })
        })
    });
</script>