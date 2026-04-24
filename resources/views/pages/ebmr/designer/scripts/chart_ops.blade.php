<script>
    let currentTableIdForChart = null;
    let chartInstances = {}; // Store Chart.js instances to destroy/update properly

    function toggleMatrixOptions(isMatrix) {
        const xAxisDiv = document.getElementById('chartXAxis').parentElement;
        const yAxisDiv = document.getElementById('chartYAxis').parentElement;
        
        if (isMatrix) {
            xAxisDiv.style.opacity = '0.5';
            xAxisDiv.style.pointerEvents = 'none';
            yAxisDiv.style.opacity = '0.5';
            yAxisDiv.style.pointerEvents = 'none';
        } else {
            xAxisDiv.style.opacity = '1';
            xAxisDiv.style.pointerEvents = 'auto';
            yAxisDiv.style.opacity = '1';
            yAxisDiv.style.pointerEvents = 'auto';
        }
    }

    function openChartCreator(tableId) {
        currentTableIdForChart = tableId;
        const item = items.find(i => i.id === tableId);
        if (!item || item.type !== 'table') return;

        const xAxisSelect = document.getElementById('chartXAxis');
        const yAxisSelect = document.getElementById('chartYAxis');
        xAxisSelect.innerHTML = '';
        yAxisSelect.innerHTML = '';

        item.columns.forEach((col, idx) => {
            const optX = document.createElement('option');
            optX.value = idx;
            optX.innerText = col.label || `Cột ${idx + 1}`;
            xAxisSelect.appendChild(optX);

            const optY = document.createElement('option');
            optY.value = idx;
            optY.innerText = col.label || `Cột ${idx + 1}`;
            yAxisSelect.appendChild(optY);
        });

        document.getElementById('isMatrixChart').checked = false;
        document.getElementById('chartMinY').value = '';
        document.getElementById('chartMaxY').value = '';
        toggleMatrixOptions(false);

        $('#chartCreatorModal').modal('show');
    }

    function extractChartData(tableItem, isMatrix, xIdx, yIdx) {
        let labels = [];
        let dataset = [];

        if (isMatrix) {
            tableItem.columns.slice(1).forEach((col, colOffset) => {
                const colIdx = colOffset + 1;
                const hourStr = stripHtml(col.label).match(/\d+/);
                const hourPart = hourStr ? hourStr[0].padStart(2, '0') : '00';
                
                tableItem.data.forEach((row) => {
                    const minuteStr = stripHtml(row[0].content || row[0]).match(/\d+/);
                    const minutePart = minuteStr ? minuteStr[0].padStart(2, '0') : '00';
                    const valCell = row[colIdx];
                    const valText = typeof valCell === 'object' ? stripHtml(valCell.content) : stripHtml(valCell);
                    
                    labels.push(`${hourPart}:${minutePart}`);
                    if (valText && valText.trim() !== '' && valText.toUpperCase() !== 'NA') {
                        const val = parseFloat(valText.replace(/,/g, ''));
                        dataset.push(isNaN(val) ? null : val);
                    } else {
                        dataset.push(null); 
                    }
                });
            });
        } else {
            tableItem.data.forEach((row) => {
                const labelCell = row[xIdx];
                const dataCell = row[yIdx];
                const labelText = typeof labelCell === 'object' ? stripHtml(labelCell.content) : stripHtml(labelCell);
                const dataText = typeof dataCell === 'object' ? stripHtml(dataCell.content) : stripHtml(dataCell);
                
                if (labelText.trim() || dataText.trim()) {
                    labels.push(labelText.trim());
                    if (dataText.toUpperCase() === 'NA' || dataText.trim() === '') {
                        dataset.push(null);
                    } else {
                        const val = parseFloat(dataText.replace(/,/g, ''));
                        dataset.push(isNaN(val) ? null : val);
                    }
                }
            });
        }
        return { labels, dataset };
    }

    function generateChartFromTable() {
        const type = document.getElementById('chartType').value;
        const title = document.getElementById('chartTitle').value;
        const isMatrix = document.getElementById('isMatrixChart').checked;
        const minY = document.getElementById('chartMinY').value;
        const maxY = document.getElementById('chartMaxY').value;
        const xIdx = parseInt(document.getElementById('chartXAxis').value);
        const yIdx = parseInt(document.getElementById('chartYAxis').value);
        
        const tableItem = items.find(i => i.id === currentTableIdForChart);
        if (!tableItem) return;

        const { labels, dataset } = extractChartData(tableItem, isMatrix, xIdx, yIdx);

        const chartId = 'blk_chart_' + Date.now();
        const sectionId = tableItem.section_id;

        const chartItem = {
            id: chartId,
            type: 'chart',
            section_id: sectionId,
            label: 'Biểu đồ: ' + (title || 'Dữ liệu'),
            chartConfig: {
                type: type,
                title: title,
                labels: labels,
                data: dataset,
                minY: minY !== '' ? parseFloat(minY) : null,
                maxY: maxY !== '' ? parseFloat(maxY) : null,
                tableSourceId: currentTableIdForChart,
                isMatrix: isMatrix,
                xIdx: xIdx,
                yIdx: yIdx
            }
        };

        const tableIdx = items.findIndex(i => i.id === currentTableIdForChart);
        items.splice(tableIdx + 1, 0, chartItem);

        $('#chartCreatorModal').modal('hide');
        renderBlocks();
        selectItem(chartId);
    }

    function syncLinkedCharts(tableId) {
        items.forEach(item => {
            if (item.type === 'chart' && item.chartConfig && item.chartConfig.tableSourceId === tableId) {
                const tableItem = items.find(i => i.id === tableId);
                if (tableItem) {
                    const { labels, dataset } = extractChartData(tableItem, item.chartConfig.isMatrix, item.chartConfig.xIdx, item.chartConfig.yIdx);
                    item.chartConfig.labels = labels;
                    item.chartConfig.data = dataset;
                    
                    const canvasId = 'chart_canvas_' + item.id;
                    if (document.getElementById(canvasId)) {
                        renderChart(canvasId, item.chartConfig);
                    }
                }
            }
        });
    }

    function stripHtml(html) {
        if (!html) return '';
        if (typeof html !== 'string') return String(html);
        const tmp = document.createElement("DIV");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || "";
    }

    function renderChart(canvasId, config) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        
        // Destroy existing instance if any
        if (chartInstances[canvasId]) {
            chartInstances[canvasId].destroy();
        }

        const ctx = canvas.getContext('2d');
        const yAxesTicks = {
            beginAtZero: config.minY === null
        };
        if (config.minY !== null) yAxesTicks.min = config.minY;
        if (config.maxY !== null) yAxesTicks.max = config.maxY;

        chartInstances[canvasId] = new Chart(ctx, {
            type: config.type,
            data: {
                labels: config.labels,
                datasets: [{
                    label: config.title || 'Dữ liệu',
                    data: config.data,
                    backgroundColor: config.type === 'bar' ? 'rgba(26, 115, 232, 0.5)' : 'rgba(26, 115, 232, 0.1)',
                    borderColor: 'rgba(26, 115, 232, 1)',
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: 'rgba(26, 115, 232, 1)',
                    fill: config.type === 'line',
                    spanGaps: false 
                }]
            },
            options: {
                animation: { duration: 0 }, // Disable animation for "Live" feel
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: yAxesTicks
                    }]
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            return data.datasets[tooltipItem.datasetIndex].label + ': ' + tooltipItem.yLabel;
                        }
                    }
                }
            }
        });
    }
</script>
