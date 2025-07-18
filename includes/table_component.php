<?php
function render_data_table($table_id, $columns, $data, $options = []) {
    $search_enabled = $options['search'] ?? true;
    $pagination_enabled = $options['pagination'] ?? true;
    $per_page = $options['per_page'] ?? 10;
    $table_class = $options['table_class'] ?? 'table table-striped table-hover';
    $container_class = $options['container_class'] ?? 'card shadow-sm';
    $title = $options['title'] ?? '';
    $export_enabled = $options['export'] ?? false;
    $filters = $options['filters'] ?? [];
    $search_id = $table_id . '_search';
    $pagination_id = $table_id . '_pagination';
    $info_id = $table_id . '_info';
    $export_id = $table_id . '_export';
    ?>
    <div class="<?php echo $container_class; ?>">
        <?php if ($search_enabled || !empty($title) || $export_enabled || !empty($filters)): ?>
        <div class="card-header bg-white border-bottom">
            <div class="row align-items-center g-3">
                <?php if (!empty($title)): ?>
                <div class="col-12 col-md-auto">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="fas fa-table me-2"></i><?php echo htmlspecialchars($title); ?>
                    </h5>
                </div>
                <?php endif; ?>
                <?php if ($search_enabled): ?>
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" id="<?php echo $search_id; ?>" class="form-control border-start-0" 
                               placeholder="Search in table...">
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($filters)): ?>
                <div class="col-12 col-md-auto">
                    <div class="d-flex gap-2 flex-wrap">
                        <?php foreach ($filters as $filter_id => $filter_options): ?>
                        <select id="<?php echo $table_id . '_filter_' . $filter_id; ?>" class="form-select form-select-sm">
                            <option value=""><?php echo $filter_options['placeholder'] ?? 'All'; ?></option>
                            <?php foreach ($filter_options['options'] as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-12 col-md-auto ms-auto">
                    <div class="d-flex align-items-center gap-3">
                        <?php if ($export_enabled): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="<?php echo $export_id; ?>" data-bs-toggle="dropdown">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="exportTable('<?php echo $table_id; ?>', 'csv')">
                                    <i class="fas fa-file-csv me-2"></i>Export as CSV
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportTable('<?php echo $table_id; ?>', 'excel')">
                                    <i class="fas fa-file-excel me-2"></i>Export as Excel
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="printTable('<?php echo $table_id; ?>')">
                                    <i class="fas fa-print me-2"></i>Print Table
                                </a></li>
                            </ul>
                        </div>
                        <?php endif; ?>
                        <div id="<?php echo $info_id; ?>" class="text-muted small d-none d-md-block"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="<?php echo $table_class; ?>" id="<?php echo $table_id; ?>">
                    <thead class="table-dark">
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <th class="border-0 text-nowrap">
                                    <?php echo htmlspecialchars($column); ?>
                                    <i class="fas fa-sort ms-1 text-muted" style="font-size: 0.75rem; cursor: pointer;"></i>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data)): ?>
                            <tr>
                                <td colspan="<?php echo count($columns); ?>" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                                        <h6>No data available</h6>
                                        <p class="mb-0 small">No records found to display</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data as $row): ?>
                                <tr class="align-middle">
                                    <?php foreach ($row as $cell): ?>
                                        <td class="border-0"><?php echo $cell; ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($pagination_enabled && !empty($data)): ?>
        <div class="card-footer bg-light border-top">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-auto">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">Show:</span>
                        <select id="<?php echo $table_id; ?>_per_page" class="form-select form-select-sm" style="width: auto;">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="text-muted small">entries</span>
                    </div>
                </div>
                <div class="col-12 col-md-auto d-md-none">
                    <div id="<?php echo $info_id; ?>_mobile" class="text-muted small text-center"></div>
                </div>
                <div class="col-12 col-md-auto ms-auto">
                    <nav aria-label="Table pagination">
                        <ul class="pagination pagination-sm justify-content-center justify-content-md-end mb-0" id="<?php echo $pagination_id; ?>">
                            <!-- Pagination will be generated by JavaScript -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        initDataTable('<?php echo $table_id; ?>', {
            search: <?php echo $search_enabled ? 'true' : 'false'; ?>,
            pagination: <?php echo $pagination_enabled ? 'true' : 'false'; ?>,
            perPage: <?php echo $per_page; ?>,
            filters: <?php echo json_encode(array_keys($filters)); ?>
        });
    });
    </script>
    <?php
}
function render_table_scripts() {
    ?>
    <script>
    function initDataTable(tableId, options = {}) {
        const table = document.getElementById(tableId);
        if (!table) return;
        const tbody = table.querySelector('tbody');
        const searchInput = document.getElementById(tableId + '_search');
        const pagination = document.getElementById(tableId + '_pagination');
        const info = document.getElementById(tableId + '_info');
        const infoMobile = document.getElementById(tableId + '_info_mobile');
        const perPageSelect = document.getElementById(tableId + '_per_page');
        let currentPage = 1;
        let perPage = options.perPage || 10;
        let filteredRows = Array.from(tbody.querySelectorAll('tr'));
        let allRows = Array.from(tbody.querySelectorAll('tr'));
        let sortColumn = -1;
        let sortDirection = 'asc';
        const headers = table.querySelectorAll('thead th');
        headers.forEach((header, index) => {
            const sortIcon = header.querySelector('.fa-sort');
            if (sortIcon) {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => sortTable(index));
            }
        });
        function sortTable(columnIndex) {
            if (sortColumn === columnIndex) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortColumn = columnIndex;
                sortDirection = 'asc';
            }
            headers.forEach((header, index) => {
                const icon = header.querySelector('i');
                if (icon) {
                    if (index === columnIndex) {
                        icon.className = `fas fa-sort-${sortDirection === 'asc' ? 'up' : 'down'} ms-1 text-primary`;
                    } else {
                        icon.className = 'fas fa-sort ms-1 text-muted';
                    }
                }
            });
            filteredRows.sort((a, b) => {
                const aText = a.cells[columnIndex]?.textContent.trim() || '';
                const bText = b.cells[columnIndex]?.textContent.trim() || '';
                const aNum = parseFloat(aText);
                const bNum = parseFloat(bText);
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
                }
                return sortDirection === 'asc' 
                    ? aText.localeCompare(bText)
                    : bText.localeCompare(aText);
            });
            currentPage = 1;
            updateDisplay();
        }
        function filterRows(searchTerm) {
            filteredRows = allRows.filter(row => {
                const text = row.textContent.toLowerCase();
                return text.includes(searchTerm.toLowerCase());
            });
            if (options.filters) {
                options.filters.forEach((filterId, index) => {
                    const filterSelect = document.getElementById(tableId + '_filter_' + filterId);
                    if (filterSelect && filterSelect.value) {
                        filteredRows = filteredRows.filter(row => {
                            const cellText = row.cells[index]?.textContent.toLowerCase() || '';
                            return cellText.includes(filterSelect.value.toLowerCase());
                        });
                    }
                });
            }
            currentPage = 1;
            updateDisplay();
        }
        function updateDisplay() {
            allRows.forEach(row => row.style.display = 'none');
            const totalRows = filteredRows.length;
            const totalPages = Math.ceil(totalRows / perPage);
            const startIndex = (currentPage - 1) * perPage;
            const endIndex = Math.min(startIndex + perPage, totalRows);
            for (let i = startIndex; i < endIndex; i++) {
                if (filteredRows[i]) {
                    filteredRows[i].style.display = '';
                }
            }
            const infoText = totalRows > 0 
                ? `Showing ${startIndex + 1} to ${endIndex} of ${totalRows} entries`
                : 'No entries to show';
            if (info) info.textContent = infoText;
            if (infoMobile) infoMobile.textContent = infoText;
            if (pagination && options.pagination) {
                updatePagination(totalPages);
            }
        }
        function updatePagination(totalPages) {
            pagination.innerHTML = '';
            if (totalPages <= 1) return;
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = '<a class="page-link" href="#" aria-label="Previous"><i class="fas fa-chevron-left"></i></a>';
            prevLi.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage > 1) {
                    currentPage--;
                    updateDisplay();
                }
            });
            pagination.appendChild(prevLi);
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            if (startPage > 1) {
                const firstLi = document.createElement('li');
                firstLi.className = 'page-item';
                firstLi.innerHTML = '<a class="page-link" href="#">1</a>';
                firstLi.addEventListener('click', (e) => {
                    e.preventDefault();
                    currentPage = 1;
                    updateDisplay();
                });
                pagination.appendChild(firstLi);
                if (startPage > 2) {
                    const ellipsisLi = document.createElement('li');
                    ellipsisLi.className = 'page-item disabled';
                    ellipsisLi.innerHTML = '<span class="page-link">...</span>';
                    pagination.appendChild(ellipsisLi);
                }
            }
            for (let i = startPage; i <= endPage; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                li.addEventListener('click', (e) => {
                    e.preventDefault();
                    currentPage = i;
                    updateDisplay();
                });
                pagination.appendChild(li);
            }
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const ellipsisLi = document.createElement('li');
                    ellipsisLi.className = 'page-item disabled';
                    ellipsisLi.innerHTML = '<span class="page-link">...</span>';
                    pagination.appendChild(ellipsisLi);
                }
                const lastLi = document.createElement('li');
                lastLi.className = 'page-item';
                lastLi.innerHTML = `<a class="page-link" href="#">${totalPages}</a>`;
                lastLi.addEventListener('click', (e) => {
                    e.preventDefault();
                    currentPage = totalPages;
                    updateDisplay();
                });
                pagination.appendChild(lastLi);
            }
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = '<a class="page-link" href="#" aria-label="Next"><i class="fas fa-chevron-right"></i></a>';
            nextLi.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage < totalPages) {
                    currentPage++;
                    updateDisplay();
                }
            });
            pagination.appendChild(nextLi);
        }
        if (searchInput && options.search) {
            searchInput.addEventListener('input', (e) => {
                filterRows(e.target.value);
            });
        }
        if (perPageSelect) {
            perPageSelect.addEventListener('change', (e) => {
                perPage = parseInt(e.target.value);
                currentPage = 1;
                updateDisplay();
            });
        }
        if (options.filters) {
            options.filters.forEach(filterId => {
                const filterSelect = document.getElementById(tableId + '_filter_' + filterId);
                if (filterSelect) {
                    filterSelect.addEventListener('change', () => {
                        filterRows(searchInput ? searchInput.value : '');
                    });
                }
            });
        }
        updateDisplay();
    }
    function exportTable(tableId, format) {
        const table = document.getElementById(tableId);
        const rows = Array.from(table.querySelectorAll('tr')).filter(row => 
            row.style.display !== 'none' && !row.querySelector('td[colspan]')
        );
        if (format === 'csv') {
            exportToCSV(rows, tableId);
        } else if (format === 'excel') {
            exportToExcel(rows, tableId);
        }
    }
    function exportToCSV(rows, filename) {
        const csv = rows.map(row => 
            Array.from(row.cells).map(cell => 
                '"' + cell.textContent.replace(/"/g, '""') + '"'
            ).join(',')
        ).join('\n');
        downloadFile(csv, filename + '.csv', 'text/csv');
    }
    function exportToExcel(rows, filename) {
        const html = '<table>' + rows.map(row => row.outerHTML).join('') + '</table>';
        downloadFile(html, filename + '.xls', 'application/vnd.ms-excel');
    }
    function downloadFile(content, filename, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    function printTable(tableId) {
        const table = document.getElementById(tableId);
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Print Table</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        @media print {
                            .table { font-size: 12px; }
                            .page-break { page-break-before: always; }
                        }
                    </style>
                </head>
                <body class="p-3">
                    <h3 class="mb-3">Table Report</h3>
                    ${table.outerHTML}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
    </script>
    <?php
}
?>
