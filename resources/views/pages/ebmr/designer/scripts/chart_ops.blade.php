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

    function getCellValueForChart(cellContent, tableId, r, c) {
        if (!cellContent) return '';
        const contentStr = typeof cellContent === 'object' ? cellContent.content : cellContent;
        
        if (window.isExecutionMode && window.executionValues) {
            // Ưu tiên đọc giá trị từ Biến số thực thụ (ebmr-field-badge)
            const fieldMatch = contentStr.match(/data-field-id=["']([^"']+)["']/);
            if (fieldMatch && fieldMatch[1]) {
                let val = window.executionValues[fieldMatch[1]];
                
                if (val && typeof val === 'object' && val.hasOwnProperty('default')) {
                    val = val.default;
                } else if (val && typeof val === 'object' && !Array.isArray(val)) {
                    const keys = Object.keys(val);
                    if (keys.length > 0) val = val[keys[0]];
                }
                
                if (val !== undefined && val !== null) return val;
            }
            
            // Nếu không, đọc giá trị lưu theo toạ độ ô (dành cho [Tự động lấy thời gian], v.v.)
            if (window.executionValues[tableId] && window.executionValues[tableId][`${r}_${c}`] !== undefined) {
                return window.executionValues[tableId][`${r}_${c}`];
            }
        }
        
        return stripHtml(contentStr);
    }

    function extractChartData(tableItem, isMatrix, xIdx, yIdx) {
        let labels = [];
        let dataset = [];
        const tableId = tableItem.id;

        if (isMatrix) {
            tableItem.columns.slice(1).forEach((col, colOffset) => {
                const colIdx = colOffset + 1;
                const hourStr = stripHtml(col.label).match(/\d+/);
                const hourPart = hourStr ? hourStr[0].padStart(2, '0') : '00';
                
                tableItem.data.forEach((row, r) => {
                    const minuteText = getCellValueForChart(row[0], tableId, r, 0);
                    const minuteStr = minuteText.match(/\d+/);
                    const minutePart = minuteStr ? minuteStr[0].padStart(2, '0') : '00';
                    
                    const valText = String(getCellValueForChart(row[colIdx], tableId, r, colIdx));
                    
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
            tableItem.data.forEach((row, r) => {
                const labelText = String(getCellValueForChart(row[xIdx], tableId, r, xIdx));
                const dataText = String(getCellValueForChart(row[yIdx], tableId, r, yIdx));
                
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
        let calculatedMinY = config.minY;
        let calculatedMaxY = config.maxY;
        
        if (config.data && Array.isArray(config.data) && config.data.length > 0) {
            const numVals = config.data
                .map(v => parseFloat(v))
                .filter(v => !isNaN(v) && isFinite(v));
                
            if (numVals.length > 0) {
                const dataMin = Math.min(...numVals);
                const dataMax = Math.max(...numVals);
                
                if (calculatedMinY !== null && dataMin < calculatedMinY) {
                    calculatedMinY = Math.floor(dataMin * 100) / 100 - 0.02;
                }
                if (calculatedMaxY !== null && dataMax > calculatedMaxY) {
                    calculatedMaxY = Math.ceil(dataMax * 100) / 100 + 0.02;
                }
            }
        }

        const yAxesTicks = {
            beginAtZero: calculatedMinY === null
        };
        if (calculatedMinY !== null) yAxesTicks.min = calculatedMinY;
        if (calculatedMaxY !== null) yAxesTicks.max = calculatedMaxY;

        if (config.customGrid && calculatedMinY !== null && calculatedMaxY !== null) {
            yAxesTicks.stepSize = (calculatedMaxY - calculatedMinY) / 10;
        }

        chartInstances[canvasId] = new Chart(ctx, {
            type: config.type,
            data: {
                labels: config.labels,
                datasets: [{
                    label: Array.isArray(config.title) ? config.title[0] : (config.title || 'Dữ liệu'),
                    data: config.data,
                    backgroundColor: config.type === 'bar' ? 'rgba(26, 115, 232, 0.5)' : 'rgba(26, 115, 232, 0.1)',
                    borderColor: 'rgba(26, 115, 232, 1)',
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#ff0000',
                    fill: config.type === 'line',
                    spanGaps: false 
                }]
            },
            options: {
                animation: { duration: 0 }, // Disable animation for "Live" feel
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: !!config.title,
                    text: typeof config.title === 'string' ? config.title.split('\n') : config.title,
                    fontSize: 14,
                    fontColor: '#000'
                },
                scales: {
                    yAxes: [{
                        ticks: yAxesTicks,
                        gridLines: {
                            color: config.customGrid ? 'rgba(0,0,0,0.3)' : 'rgba(0, 0, 0, 0.1)',
                            lineWidth: config.customGrid ? 1 : 1
                        }
                    }],
                    xAxes: [{
                        gridLines: {
                            color: config.customGrid ? 'rgba(0,0,0,0.3)' : 'rgba(0, 0, 0, 0.1)',
                            lineWidth: config.customGrid ? 1 : 1
                        }
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

    function generateWeightChartTable() {
        const weight = parseFloat(document.getElementById('wcTargetWeight').value) || 7.10;
        const devPercent = parseFloat(document.getElementById('wcDeviation').value) || 3;
        const freq = parseInt(document.getElementById('wcFrequency').value) || 15;

        const maxDev = (weight * devPercent / 100);
        const minY = (weight - maxDev).toFixed(2);
        const maxY = (weight + maxDev).toFixed(2);

        const tableId = 'blk_table_' + Date.now();
        const chartId = 'blk_chart_' + Date.now();
        const sectionId = window.activeSectionId || null;

        // Tạo 3 biến số thật cho 3 dòng mặc định
        const fieldId1 = 'field_' + Math.floor(Math.random() * 1000000);
        const fieldId2 = 'field_' + Math.floor(Math.random() * 1000000);
        const fieldId3 = 'field_' + Math.floor(Math.random() * 1000000);

        fieldsConfig[fieldId1] = {
            id: fieldId1, name: `Khối lượng TB 1`, label: 'Nhập Số', type: 'number',
            validation: { required: false, min: null, max: null, decimal_places: 2 },
            options: [], section_id: sectionId, block_id: tableId, instruction: ''
        };
        fieldsConfig[fieldId2] = {
            id: fieldId2, name: `Khối lượng TB 2`, label: 'Nhập Số', type: 'number',
            validation: { required: false, min: null, max: null, decimal_places: 2 },
            options: [], section_id: sectionId, block_id: tableId, instruction: ''
        };
        fieldsConfig[fieldId3] = {
            id: fieldId3, name: `Khối lượng TB 3`, label: 'Nhập Số', type: 'number',
            validation: { required: false, min: null, max: null, decimal_places: 2 },
            options: [], section_id: sectionId, block_id: tableId, instruction: ''
        };

        const fb1 = `<span contenteditable="false" class="ebmr-field-badge" data-field-id="${fieldId1}" onclick="selectField(event, '${fieldId1}')"></span>\u200B`;
        const fb2 = `<span contenteditable="false" class="ebmr-field-badge" data-field-id="${fieldId2}" onclick="selectField(event, '${fieldId2}')"></span>\u200B`;
        const fb3 = `<span contenteditable="false" class="ebmr-field-badge" data-field-id="${fieldId3}" onclick="selectField(event, '${fieldId3}')"></span>\u200B`;

        // 1. Create the Table
        const tableItem = {
            id: tableId,
            type: 'table',
            section_id: sectionId,
            label: 'Bảng nhập Khối lượng Trung bình',
            borderMode: 'all',
            borderWeight: '1px',
            hideHeader: false,
            canAddRows: true, // Cho phép người dùng cấp 2 thêm dòng
            freq_minutes: freq, // Lưu lại tần suất lấy mẫu để đếm ngược
            rows: 3,
            cols: 4,
            columns: [
                { label: 'Thời gian lấy mẫu', width: '25%' },
                { label: 'W10', width: '25%' },
                { label: 'Người thực hiện', width: '25%' },
                { label: 'Người kiểm tra', width: '25%' }
            ],
            rowHeights: ['auto', 'auto', 'auto'],
            data: [
                [
                    { content: `<span class="execution-badge time" data-freq="${freq}">[Tự động lấy thời gian]</span>` },
                    { content: fb1 },
                    { content: '<span class="execution-badge executor">[Người thực hiện]</span>' },
                    { content: '<span class="execution-badge checker">[Người kiểm tra]</span>' }
                ],
                [
                    { content: `<span class="execution-badge time" data-freq="${freq}">[Tự động lấy thời gian]</span>` },
                    { content: fb2 },
                    { content: '<span class="execution-badge executor">[Người thực hiện]</span>' },
                    { content: '<span class="execution-badge checker">[Người kiểm tra]</span>' }
                ],
                [
                    { content: `<span class="execution-badge time" data-freq="${freq}">[Tự động lấy thời gian]</span>` },
                    { content: fb3 },
                    { content: '<span class="execution-badge executor">[Người thực hiện]</span>' },
                    { content: '<span class="execution-badge checker">[Người kiểm tra]</span>' }
                ]
            ]
        };

        // 2. Create the Chart
        const chartItem = {
            id: chartId,
            type: 'chart',
            section_id: sectionId,
            label: `Biểu đồ Khối lượng (W10: ${weight}g ± ${devPercent}%)`,
            chartConfig: {
                type: 'line',
                title: `VẼ BIỂU ĐỒ THEO KHỐI LƯỢNG MỖI 10 VIÊN\nTiêu chuẩn: Khối lượng 10 viên ± ${devPercent}% = ${weight} g ± ${devPercent}% (${minY} - ${maxY} g)\nTần suất kiểm tra: Mỗi ${freq} phút`,
                labels: ['1', '2', '3'],
                data: [null, null, null],
                minY: parseFloat(minY),
                maxY: parseFloat(maxY),
                tableSourceId: tableId,
                isMatrix: false,
                xIdx: 0,
                yIdx: 1,
                customGrid: true // Flag to render grid density
            }
        };

        // Insert into items
        let insertIdx = items.length;
        if (sectionId) {
            // Put it at the end of the section
            const secBlocks = items.filter(i => i.section_id === sectionId);
            if (secBlocks.length > 0) {
                const lastBlock = secBlocks[secBlocks.length - 1];
                insertIdx = items.findIndex(i => i.id === lastBlock.id) + 1;
            }
        }
        
        items.splice(insertIdx, 0, chartItem);
        items.splice(insertIdx + 1, 0, tableItem);

        $('#weightChartCreatorModal').modal('hide');
        renderBlocks();
        saveState();
        
        setTimeout(() => {
            const el = document.getElementById(tableId);
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }
</script>
