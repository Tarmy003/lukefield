        let currentEmployeeId = null;
        let generatedPayslips = [];
        let employeesData = {};

        // Load employee salaries
        function loadEmployeeSalaries() {
            const payslipMonth = document.getElementById('payslipMonth').value;
            const paymentDate = document.getElementById('paymentDate').value;
            const department = document.getElementById('departmentFilter').value;

            if (!payslipMonth || !paymentDate) {
                showAlert('Please select payslip month and payment date', 'warning');
                return;
            }

            // Show loading
            const tbody = document.getElementById('employeeSalariesBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading employee salaries...</p>
                    </td>
                </tr>
            `;

            document.getElementById('employeeSalariesSection').style.display = 'block';

            // Fetch employee salaries
            fetch('/payslips/load-employees', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    payslip_month: payslipMonth,
                    payment_date: paymentDate,
                    department: department
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayEmployeeSalaries(data.employees);
                        employeesData = data.employees;
                    } else {
                        showAlert(data.message || 'Error loading employees', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error loading employees', 'error');
                    tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="text-center py-4 text-danger">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <p>Error loading employee data</p>
                        </td>
                    </tr>
                `;
                });
        }

        function displayEmployeeSalaries(employees) {
            const tbody = document.getElementById('employeeSalariesBody');

            if (employees.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No employees found</h5>
                            <p class="text-muted">No active salary records found for the selected criteria.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = employees.map(emp => `
                <tr data-employee-id="${emp.id}">
                    <td>
                        <div class="form-check">
                            <input class="form-check-input employee-checkbox" type="checkbox" value="${emp.id}">
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar-sm rounded bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user text-muted"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-1">${emp.name}</h6>
                                <p class="text-muted mb-0 small">ID: ${emp.employee_id || 'N/A'}</p>
                                <p class="text-muted mb-0 small">${emp.email}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-info-subtle text-info">${emp.department || 'N/A'}</span>
                    </td>
                    <td>
                        <span class="fw-medium text-primary">₦${parseFloat(emp.basic_salary || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                    </td>
                    <td>
                        <span class="fw-medium text-success">₦${parseFloat(emp.standard_earnings || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                    </td>
                    <td>
                        <span class="fw-medium text-info extra-earnings-display" id="extraEarnings_${emp.id}">₦0.00</span>
                        <button class="btn btn-outline-success btn-sm ms-2" onclick="openExtraEarningsModal(${emp.id}, '${emp.name}')">
                            <i class="fas fa-plus"></i>
                        </button>
                    </td>
                    <td>
                        <span class="fw-medium text-warning">₦${parseFloat(emp.standard_deductions || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                    </td>
                    <td>
                        <span class="fw-medium text-danger extra-deductions-display" id="extraDeductions_${emp.id}">₦0.00</span>
                        <button class="btn btn-outline-danger btn-sm ms-2" onclick="openExtraDeductionsModal(${emp.id}, '${emp.name}')">
                            <i class="fas fa-plus"></i>
                        </button>
                    </td>
                    <td>
                        <span class="fw-bold text-dark net-pay-display" id="netPay_${emp.id}">
                            ₦${parseFloat(emp.standard_earnings - emp.standard_deductions || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-outline-primary btn-sm" onclick="previewPayslip(${emp.id})">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                    </td>
                </tr>
            `).join('');

            // Initialize extra earnings and deductions storage
            employees.forEach(emp => {
                if (!employeesData[emp.id]) {
                    employeesData[emp.id] = {
                        ...emp,
                        extra_earnings: [],
                        extra_deductions: []
                    };
                }
            });
        }

        // Extra earnings functions
        function openExtraEarningsModal(employeeId, employeeName) {
            currentEmployeeId = employeeId;
            document.getElementById('earningsEmployeeName').textContent = employeeName;

            // Load existing extra earnings if any
            const existingEarnings = employeesData[employeeId]?.extra_earnings || [];
            const container = document.getElementById('extraEarningsContainer');

            if (existingEarnings.length > 0) {
                container.innerHTML = existingEarnings.map((earning, index) => `
                    <div class="row extra-earning-row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Earning Name</label>
                                <input type="text" class="form-control" name="earning_name[]" value="${earning.name}" placeholder="e.g., Overtime, Bonus">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Amount (₦)</label>
                                <input type="number" class="form-control earning-amount" name="earning_amount[]" value="${earning.amount}" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label">Taxable</label>
                                <select class="form-select" name="earning_taxable[]">
                                    <option value="1" ${earning.is_taxable ? 'selected' : ''}>Yes</option>
                                    <option value="0" ${!earning.is_taxable ? 'selected' : ''}>No</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control" name="earning_description[]" value="${earning.description || ''}" placeholder="Optional">
                                ${index > 0 ? '<button type="button" class="btn btn-danger btn-sm mt-1" onclick="removeExtraEarning(this)"><i class="fas fa-trash"></i></button>' : ''}
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            calculateTotalExtraEarnings();
            const modal = new bootstrap.Modal(document.getElementById('extraEarningsModal'));
            modal.show();
        }

        function addExtraEarning() {
            const container = document.getElementById('extraEarningsContainer');
            const newRow = document.createElement('div');
            newRow.className = 'row extra-earning-row';
            newRow.innerHTML = `
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Earning Name</label>
                        <input type="text" class="form-control" name="earning_name[]" placeholder="e.g., Overtime, Bonus">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Amount (₦)</label>
                        <input type="number" class="form-control earning-amount" name="earning_amount[]" step="0.01" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label class="form-label">Taxable</label>
                        <select class="form-select" name="earning_taxable[]">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" name="earning_description[]" placeholder="Optional">
                        <button type="button" class="btn btn-danger btn-sm mt-1" onclick="removeExtraEarning(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newRow);

            // Add event listener for amount calculation
            newRow.querySelector('.earning-amount').addEventListener('input', calculateTotalExtraEarnings);
        }

        function removeExtraEarning(button) {
            button.closest('.extra-earning-row').remove();
            calculateTotalExtraEarnings();
        }

        function calculateTotalExtraEarnings() {
            const amounts = document.querySelectorAll('.earning-amount');
            let total = 0;
            amounts.forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            document.getElementById('totalExtraEarnings').textContent = total.toLocaleString('en-US', { minimumFractionDigits: 2 });
        }

        function saveExtraEarnings() {
            const names = document.querySelectorAll('input[name="earning_name[]"]');
            const amounts = document.querySelectorAll('input[name="earning_amount[]"]');
            const taxables = document.querySelectorAll('select[name="earning_taxable[]"]');
            const descriptions = document.querySelectorAll('input[name="earning_description[]"]');

            const earnings = [];
            for (let i = 0; i < names.length; i++) {
                if (names[i].value.trim() && amounts[i].value) {
                    earnings.push({
                        name: names[i].value.trim(),
                        amount: parseFloat(amounts[i].value),
                        is_taxable: taxables[i].value === '1',
                        description: descriptions[i].value.trim()
                    });
                }
            }

            // Save to employeesData
            if (!employeesData[currentEmployeeId]) {
                employeesData[currentEmployeeId] = { extra_earnings: [], extra_deductions: [] };
            }
            employeesData[currentEmployeeId].extra_earnings = earnings;

            // Update display
            const totalExtraEarnings = earnings.reduce((sum, earning) => sum + earning.amount, 0);
            document.getElementById(`extraEarnings_${currentEmployeeId}`).textContent =
                '₦' + totalExtraEarnings.toLocaleString('en-US', { minimumFractionDigits: 2 });

            // Update net pay
            updateNetPay(currentEmployeeId);

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('extraEarningsModal'));
            modal.hide();

            showAlert('Extra earnings saved successfully', 'success');
        }

        // Extra deductions functions (similar to earnings)
        function openExtraDeductionsModal(employeeId, employeeName) {
            currentEmployeeId = employeeId;
            document.getElementById('deductionsEmployeeName').textContent = employeeName;

            // Load existing extra deductions if any
            const existingDeductions = employeesData[employeeId]?.extra_deductions || [];
            const container = document.getElementById('extraDeductionsContainer');

            if (existingDeductions.length > 0) {
                container.innerHTML = existingDeductions.map((deduction, index) => `
                    <div class="row extra-deduction-row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Deduction Name</label>
                                <input type="text" class="form-control" name="deduction_name[]" value="${deduction.name}" placeholder="e.g., Loan, Advance">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Amount (₦)</label>
                                <input type="number" class="form-control deduction-amount" name="deduction_amount[]" value="${deduction.amount}" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label">Pre-tax</label>
                                <select class="form-select" name="deduction_pretax[]">
                                    <option value="0" ${!deduction.is_pretax ? 'selected' : ''}>No</option>
                                    <option value="1" ${deduction.is_pretax ? 'selected' : ''}>Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control" name="deduction_description[]" value="${deduction.description || ''}" placeholder="Optional">
                                ${index > 0 ? '<button type="button" class="btn btn-danger btn-sm mt-1" onclick="removeExtraDeduction(this)"><i class="fas fa-trash"></i></button>' : ''}
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            calculateTotalExtraDeductions();
            const modal = new bootstrap.Modal(document.getElementById('extraDeductionsModal'));
            modal.show();
        }

        function addExtraDeduction() {
            const container = document.getElementById('extraDeductionsContainer');
            const newRow = document.createElement('div');
            newRow.className = 'row extra-deduction-row';
            newRow.innerHTML = `
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Deduction Name</label>
                        <input type="text" class="form-control" name="deduction_name[]" placeholder="e.g., Loan, Advance">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Amount (₦)</label>
                        <input type="number" class="form-control deduction-amount" name="deduction_amount[]" step="0.01" placeholder="0.00">
                    </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label class="form-label">Pre-tax</label>
                        <select class="form-select" name="deduction_pretax[]">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" name="deduction_description[]" placeholder="Optional">
                        <button type="button" class="btn btn-danger btn-sm mt-1" onclick="removeExtraDeduction(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newRow);

            // Add event listener for amount calculation
            newRow.querySelector('.deduction-amount').addEventListener('input', calculateTotalExtraDeductions);
        }

        function removeExtraDeduction(button) {
            button.closest('.extra-deduction-row').remove();
            calculateTotalExtraDeductions();
        }

        function calculateTotalExtraDeductions() {
            const amounts = document.querySelectorAll('.deduction-amount');
            let total = 0;
            amounts.forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            document.getElementById('totalExtraDeductions').textContent = total.toLocaleString('en-US', { minimumFractionDigits: 2 });
        }

        function saveExtraDeductions() {
            const names = document.querySelectorAll('input[name="deduction_name[]"]');
            const amounts = document.querySelectorAll('input[name="deduction_amount[]"]');
            const pretaxes = document.querySelectorAll('select[name="deduction_pretax[]"]');
            const descriptions = document.querySelectorAll('input[name="deduction_description[]"]');

            const deductions = [];
            for (let i = 0; i < names.length; i++) {
                if (names[i].value.trim() && amounts[i].value) {
                    deductions.push({
                        name: names[i].value.trim(),
                        amount: parseFloat(amounts[i].value),
                        is_pretax: pretaxes[i].value === '1',
                        description: descriptions[i].value.trim()
                    });
                }
            }

            // Save to employeesData
            if (!employeesData[currentEmployeeId]) {
                employeesData[currentEmployeeId] = { extra_earnings: [], extra_deductions: [] };
            }
            employeesData[currentEmployeeId].extra_deductions = deductions;

            // Update display
            const totalExtraDeductions = deductions.reduce((sum, deduction) => sum + deduction.amount, 0);
            document.getElementById(`extraDeductions_${currentEmployeeId}`).textContent =
                '₦' + totalExtraDeductions.toLocaleString('en-US', { minimumFractionDigits: 2 });

            // Update net pay
            updateNetPay(currentEmployeeId);

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('extraDeductionsModal'));
            modal.hide();

            showAlert('Extra deductions saved successfully', 'success');
        }

        function updateNetPay(employeeId) {
            const employee = employeesData[employeeId];
            if (!employee) return;

            const standardEarnings = parseFloat(employee.standard_earnings || 0);
            const standardDeductions = parseFloat(employee.standard_deductions || 0);

            const extraEarningsTotal = (employee.extra_earnings || [])
                .reduce((sum, earning) => sum + earning.amount, 0);

            const extraDeductionsTotal = (employee.extra_deductions || [])
                .reduce((sum, deduction) => sum + deduction.amount, 0);

            const netPay = standardEarnings + extraEarningsTotal - standardDeductions - extraDeductionsTotal;

            const netPayElement = document.getElementById(`netPay_${employeeId}`);
            if (netPayElement) {
                netPayElement.textContent = '₦' + netPay.toLocaleString('en-US', { minimumFractionDigits: 2 });
                netPayElement.className = 'fw-bold ' + (netPay >= 0 ? 'text-success' : 'text-danger');
            }
        }

        // Employee selection functions
        function selectAllEmployees() {
            const checkboxes = document.querySelectorAll('.employee-checkbox');
            const masterCheckbox = document.getElementById('selectAllEmployees');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);

            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });

            masterCheckbox.checked = !allChecked;
        }

        // Master checkbox handler
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('selectAllEmployees').addEventListener('change', function () {
                const checkboxes = document.querySelectorAll('.employee-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });

            // Add event listeners for individual checkboxes
            document.addEventListener('change', function (e) {
                if (e.target.classList.contains('employee-checkbox')) {
                    const allCheckboxes = document.querySelectorAll('.employee-checkbox');
                    const checkedCheckboxes = document.querySelectorAll('.employee-checkbox:checked');
                    const masterCheckbox = document.getElementById('selectAllEmployees');

                    masterCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
                    masterCheckbox.checked = checkedCheckboxes.length === allCheckboxes.length;
                }

                if (e.target.classList.contains('earning-amount')) {
                    calculateTotalExtraEarnings();
                }

                if (e.target.classList.contains('deduction-amount')) {
                    calculateTotalExtraDeductions();
                }
            });
        });

        // Generate payslips functions
        function generateSelectedPayslips() {
            const selectedEmployees = Array.from(document.querySelectorAll('.employee-checkbox:checked'))
                .map(cb => parseInt(cb.value));

            if (selectedEmployees.length === 0) {
                showAlert('Please select at least one employee', 'warning');
                return;
            }

            const payslipMonth = document.getElementById('payslipMonth').value;
            const paymentDate = document.getElementById('paymentDate').value;

            // Prepare payslip data
            const payslipData = selectedEmployees.map(empId => {
                const employee = employeesData[empId];
                return {
                    employee_id: empId,
                    salary_id: employee.salary_id,
                    payslip_month: payslipMonth,
                    payment_date: paymentDate,
                    extra_earnings: employee.extra_earnings || [],
                    extra_deductions: employee.extra_deductions || []
                };
            });

            // Show loading
            showAlert('Generating payslips...', 'info');

            // Generate payslips
            fetch('/payslips/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    payslips: payslipData
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        generatedPayslips = data.payslips;
                        showGeneratedPayslips(data.payslips);
                        showAlert(`${data.payslips.length} payslips generated successfully!`, 'success');

                        // Enable bulk email button
                        document.getElementById('sendBulkBtn').disabled = false;
                    } else {
                        showAlert(data.message || 'Error generating payslips', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error generating payslips', 'error');
                });
        }

        function showGeneratedPayslips(payslips) {
            document.getElementById('generatedPayslipsSection').style.display = 'block';
            document.getElementById('generatedCount').textContent = `${payslips.length} Payslips Generated`;

            const summaryContainer = document.getElementById('payslipsSummary');
            summaryContainer.innerHTML = `
                <div class="row">
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Gross Pay</th>
                                        <th>Total Deductions</th>
                                        <th>Net Pay</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${payslips.map(payslip => `
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-xs me-2">
                                                        <div class="avatar-title rounded-circle bg-primary-subtle text-primary">
                                                            ${payslip.employee_name.charAt(0)}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">${payslip.employee_name}</h6>
                                                        <small class="text-muted">${payslip.employee_email}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info">${payslip.department || 'N/A'}</span>
                                            </td>
                                            <td>
                                                <span class="fw-medium text-success">₦${parseFloat(payslip.gross_pay).toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                                            </td>
                                            <td>
                                                <span class="fw-medium text-danger">₦${parseFloat(payslip.total_deductions).toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-primary">₦${parseFloat(payslip.net_pay).toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="fas fa-check-circle me-1"></i>Generated
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="downloadPayslip(${payslip.id})">
                                                        <i class="fas fa-download"></i> PDF
                                                    </button>
                                                    <button class="btn btn-outline-success" onclick="sendSinglePayslip(${payslip.id})">
                                                        <i class="fas fa-envelope"></i> Email
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card bg-primary-subtle border-primary">
                            <div class="card-body text-center">
                                <h4 class="text-primary mb-1">₦${payslips.reduce((sum, p) => sum + parseFloat(p.gross_pay), 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</h4>
                                <p class="text-muted mb-0">Total Gross Pay</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger-subtle border-danger">
                            <div class="card-body text-center">
                                <h4 class="text-danger mb-1">₦${payslips.reduce((sum, p) => sum + parseFloat(p.total_deductions), 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</h4>
                                <p class="text-muted mb-0">Total Deductions</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success-subtle border-success">
                            <div class="card-body text-center">
                                <h4 class="text-success mb-1">₦${payslips.reduce((sum, p) => sum + parseFloat(p.net_pay), 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</h4>
                                <p class="text-muted mb-0">Total Net Pay</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info-subtle border-info">
                            <div class="card-body text-center">
                                <h4 class="text-info mb-1">${payslips.length}</h4>
                                <p class="text-muted mb-0">Employees</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Bulk email function
        function sendBulkPayslips() {
            if (generatedPayslips.length === 0) {
                showAlert('No payslips to send', 'warning');
                return;
            }

            // Show confirmation modal
            if (confirm(`Are you sure you want to send ${generatedPayslips.length} payslips via email?`)) {

                // Show loading
                const button = document.getElementById('sendBulkBtn');
                const originalText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sending...';

                fetch('/payslips/send-bulk', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        payslip_ids: generatedPayslips.map(p => p.id)
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert(`${data.sent_count} payslips sent successfully!`, 'success');
                        } else {
                            showAlert(data.message || 'Error sending payslips', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('Error sending payslips', 'error');
                    })
                    .finally(() => {
                        button.disabled = false;
                        button.innerHTML = originalText;
                    });
            }
        }

        // Individual payslip functions
        function downloadPayslip(payslipId) {
            window.open(`/payslips/${payslipId}/download`, '_blank');
        }

        function sendSinglePayslip(payslipId) {
            const button = event.target;
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch(`/payslips/${payslipId}/send`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Payslip sent successfully!', 'success');
                    } else {
                        showAlert(data.message || 'Error sending payslip', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error sending payslip', 'error');
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
        }

        function previewPayslip(employeeId) {
            const employee = employeesData[employeeId];
            if (!employee) {
                showAlert('Employee data not found', 'error');
                return;
            }

            const payslipMonth = document.getElementById('payslipMonth').value;
            const paymentDate = document.getElementById('paymentDate').value;

            // Create preview data
            const previewData = {
                employee_id: employeeId,
                employee: employee,
                payslip_month: payslipMonth,
                payment_date: paymentDate,
                extra_earnings: employee.extra_earnings || [],
                extra_deductions: employee.extra_deductions || []
            };

            // Open preview in new window
            const previewWindow = window.open('', '_blank', 'width=800,height=1000');
            previewWindow.document.write(generatePayslipPreview(previewData));
        }

        function generatePayslipPreview(data) {
            const standardEarnings = parseFloat(data.employee.standard_earnings || 0);
            const standardDeductions = parseFloat(data.employee.standard_deductions || 0);
            const extraEarningsTotal = data.extra_earnings.reduce((sum, earning) => sum + earning.amount, 0);
            const extraDeductionsTotal = data.extra_deductions.reduce((sum, deduction) => sum + deduction.amount, 0);
            const grossPay = standardEarnings + extraEarningsTotal;
            const totalDeductions = standardDeductions + extraDeductionsTotal;
            const netPay = grossPay - totalDeductions;

            return `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Payslip Preview - ${data.employee.name}</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
                <style>
                    body { font-family: Arial, sans-serif; }
                    .payslip-header { border-bottom: 3px solid #007bff; }
                    .payslip-footer { border-top: 2px solid #dee2e6; }
                    @media print {
                        .no-print { display: none !important; }
                        body { -webkit-print-color-adjust: exact; }
                    }
                </style>
            </head>
            <body class="bg-light">
                <div class="container mt-4">
                    <div class="card shadow">
                        <div class="card-header payslip-header bg-primary text-white">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h3 class="mb-0"><i class="fas fa-building me-2"></i>Company Name</h3>
                                    <small>Payslip for ${new Date(data.payslip_month).toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</small>
                                </div>
                                <div class="col-auto no-print">
                                    <button class="btn btn-light btn-sm" onclick="window.print()">
                                        <i class="fas fa-print me-1"></i>Print
                                    </button>
                                    <button class="btn btn-outline-light btn-sm" onclick="window.close()">
                                        <i class="fas fa-times me-1"></i>Close
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <!-- Employee Details -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3">Employee Details</h5>
                                    <table class="table table-borderless table-sm">
                                        <tr><td class="fw-medium">Name:</td><td>${data.employee.name}</td></tr>
                                        <tr><td class="fw-medium">Employee ID:</td><td>${data.employee.employee_id || 'N/A'}</td></tr>
                                        <tr><td class="fw-medium">Email:</td><td>${data.employee.email}</td></tr>
                                        <tr><td class="fw-medium">Department:</td><td>${data.employee.department || 'N/A'}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3">Pay Period Details</h5>
                                    <table class="table table-borderless table-sm">
                                        <tr><td class="fw-medium">Pay Period:</td><td>${new Date(data.payslip_month).toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</td></tr>
                                        <tr><td class="fw-medium">Payment Date:</td><td>${new Date(data.payment_date).toLocaleDateString()}</td></tr>
                                        <tr><td class="fw-medium">Generated:</td><td>${new Date().toLocaleDateString()}</td></tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Earnings and Deductions -->
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="text-success mb-3"><i class="fas fa-plus-circle me-2"></i>Earnings</h5>
                                    <table class="table table-bordered table-sm">
                                        <thead class="table-success">
                                            <tr><th>Description</th><th class="text-end">Amount (₦)</th></tr>
                                        </thead>
                                        <tbody>
                                            <tr><td>Basic Salary</td><td class="text-end">${parseFloat(data.employee.basic_salary || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td></tr>
                                            <tr><td>Medical Allowance</td><td class="text-end">${parseFloat(data.employee.medical_allowance || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td></tr>
                                            <tr><td>Housing Allowance</td><td class="text-end">${parseFloat(data.employee.housing_allowance || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td></tr>
                                            <tr><td>Transport Allowance</td><td class="text-end">${parseFloat(data.employee.transport_allowance || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td></tr>
                                            ${data.extra_earnings.map(earning =>
                `<tr><td>${earning.name}</td><td class="text-end">${earning.amount.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td></tr>`
            ).join('')}
                                            <tr class="table-success fw-bold">
                                                <td>GROSS PAY</td>
                                                <td class="text-end">₦${grossPay.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="col-md-6">
                                    <h5 class="text-danger mb-3"><i class="fas fa-minus-circle me-2"></i>Deductions</h5>
                                    <table class="table table-bordered table-sm">
                                        <thead class="table-danger">
                                            <tr><th>Description</th><th class="text-end">Amount (₦)</th></tr>
                                        </thead>
                                        <tbody>
                                            <tr><td>PAYE Tax</td><td class="text-end">${parseFloat(data.employee.paye_tax || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td></tr>
                                            <tr><td>Pension Contribution</td><td class="text-end">${parseFloat(data.employee.pension_contribution || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td></tr>
                                            <tr><td>NHF Contribution</td><td class="text-end">${parseFloat(data.employee.nhf_contribution || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td></tr>
                                            ${data.extra_deductions.map(deduction =>
                `<tr><td>${deduction.name}</td><td class="text-end">${deduction.amount.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td></tr>`
            ).join('')}
                                            <tr class="table-danger fw-bold">
                                                <td>TOTAL DEDUCTIONS</td>
                                                <td class="text-end">₦${totalDeductions.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Net Pay Summary -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body text-center">
                                            <h3 class="mb-0">NET PAY: ₦${netPay.toLocaleString('en-US', { minimumFractionDigits: 2 })}</h3>
                                            <p class="mb-0">Amount to be paid to employee</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer payslip-footer text-center text-muted">
                            <small>This is a computer generated payslip and does not require signature</small><br>
                            <small>Generated on ${new Date().toLocaleString()}</small>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            `;
        }

        // Utility functions
        function showAlert(message, type = 'info') {
            const alertClass = {
                'success': 'alert-success',
                'error': 'alert-danger',
                'warning': 'alert-warning',
                'info': 'alert-info'
            }[type] || 'alert-info';

            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                     style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', alertHtml);

            // Auto dismiss after 5 seconds
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }

        // Initialize tooltips and other Bootstrap components
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize tooltips if needed
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
