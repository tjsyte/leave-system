</div> <!-- End Content Area -->
    </div> <!-- End Main Content -->
    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            function checkScreenSize() {
                if (window.innerWidth <= 1024) {
                    sidebarToggle.style.display = 'inline-flex';
                } else {
                    sidebarToggle.style.display = 'none';
                    sidebar.classList.remove('active');
                }
            }
            checkScreenSize();
            window.addEventListener('resize', checkScreenSize);
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 1024) {
                    if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                        sidebar.classList.remove('active');
                    }
                }
            });
        });
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }
        function searchTable(inputId, tableId) {
            const input = document.getElementById(inputId);
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            input.addEventListener('keyup', function() {
                const filter = input.value.toLowerCase();
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const cells = row.getElementsByTagName('td');
                    let found = false;
                    for (let j = 0; j < cells.length; j++) {
                        const cell = cells[j];
                        if (cell.textContent.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                    row.style.display = found ? '' : 'none';
                }
            });
        }
        function filterTable(selectId, tableId, columnIndex) {
            const select = document.getElementById(selectId);
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            select.addEventListener('change', function() {
                const filter = select.value.toLowerCase();
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const cell = row.getElementsByTagName('td')[columnIndex];
                    if (filter === '' || cell.textContent.toLowerCase().indexOf(filter) > -1) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }
        function validateDateRange(fromId, toId) {
            const fromDate = document.getElementById(fromId);
            const toDate = document.getElementById(toId);
            fromDate.addEventListener('change', function() {
                toDate.min = fromDate.value;
                if (toDate.value && toDate.value < fromDate.value) {
                    toDate.value = fromDate.value;
                }
            });
            toDate.addEventListener('change', function() {
                if (fromDate.value && toDate.value < fromDate.value) {
                    alert('End date cannot be earlier than start date');
                    toDate.value = fromDate.value;
                }
            });
        }
        function calculateDays(fromId, toId, resultId) {
            const fromDate = document.getElementById(fromId);
            const toDate = document.getElementById(toId);
            const result = document.getElementById(resultId);
            function updateDays() {
                if (fromDate.value && toDate.value) {
                    const from = new Date(fromDate.value);
                    const to = new Date(toDate.value);
                    const timeDiff = to.getTime() - from.getTime();
                    const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
                    if (dayDiff > 0) {
                        result.textContent = dayDiff + ' day' + (dayDiff > 1 ? 's' : '');
                    } else {
                        result.textContent = '';
                    }
                }
            }
            fromDate.addEventListener('change', updateDays);
            toDate.addEventListener('change', updateDays);
        }
        function validateForm(formId) {
            const form = document.getElementById(formId);
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--danger-color)';
                    isValid = false;
                } else {
                    field.style.borderColor = 'var(--border-color)';
                }
            });
            return isValid;
        }
        function setLoadingState(buttonId, loading = true) {
            const button = document.getElementById(buttonId);
            const originalText = button.innerHTML;
            if (loading) {
                button.disabled = true;
                button.innerHTML = '<span class="loading"></span> Processing...';
                button.dataset.originalText = originalText;
            } else {
                button.disabled = false;
                button.innerHTML = button.dataset.originalText || originalText;
            }
        }
    </script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Table Component Scripts -->
    <?php 
    if (function_exists('render_table_scripts')) {
        render_table_scripts();
    }
    ?>
</body>
</html>
