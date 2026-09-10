/* =========================================================
   REPORTS MODULE JAVASCRIPT
   File: assets/js/reports.js
   Purpose: Category -> Report selection, filter visibility,
            date validation, and print handling.
   ========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    const categorySelect = document.getElementById('category');
    const reportSelect = document.getElementById('report');

    const statusField = document.querySelector('.filter-status');
    const speciesField = document.querySelector('.filter-species');
    const transactionField = document.querySelector('.filter-transaction');
    const inventoryCategoryField = document.querySelector('.filter-inventory-category');

    const reportOptions = {
        records: [
            { value: 'all_records', label: 'All Reports' },
            { value: 'customer_records', label: 'Customer Records' },
            { value: 'pet_records', label: 'Pet Records' },
            { value: 'appointment_records', label: 'Appointment Records' },
            { value: 'medical_records', label: 'Medical Records' }
        ],
        sales: [
            { value: 'all_sales', label: 'All Reports' },
            { value: 'sales_summary', label: 'Sales Summary' },
            { value: 'sales_details', label: 'Sales Details' }
        ],
        inventory: [
            { value: 'all_inventory', label: 'All Reports' },
            { value: 'current_inventory', label: 'Current Inventory' },
            { value: 'low_stock', label: 'Low Stock' },
            { value: 'expiration', label: 'Expiration' }
        ]
    };

    function showField(field, visible) {
        if (field) {
            field.style.display = visible ? 'block' : 'none';
        }
    }

    function populateReports(selectedValue) {

        if (!reportSelect) {
            return;
        }

        const category = categorySelect ? categorySelect.value : '';
        const options = reportOptions[category] || [];

        reportSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = category ? 'Select Report' : 'Select Category First';
        reportSelect.appendChild(placeholder);

        options.forEach(function (option) {
            const optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.label;

            if (option.value === selectedValue) {
                optionElement.selected = true;
            }

            reportSelect.appendChild(optionElement);
        });

        reportSelect.disabled = options.length === 0;
    }

    function updateFilters() {

        const category = categorySelect ? categorySelect.value : '';
        const report = reportSelect ? reportSelect.value : '';

        const isRecords = category === 'records';
        const isPetRecords = report === 'pet_records';
        const isMedicalRecords = report === 'medical_records';
        const isSales = category === 'sales';
        const isInventory = category === 'inventory';

        showField(
            statusField,
            report === 'customer_records' ||
            report === 'pet_records' ||
            report === 'appointment_records' ||
            report === 'all_records' ||
            isSales
        );

        showField(
            speciesField,
            isPetRecords || isMedicalRecords || report === 'all_records'
        );

        showField(
            transactionField,
            report === 'sales_summary' || report === 'sales_details' || report === 'all_sales'
        );

        showField(
            inventoryCategoryField,
            isInventory
        );
    }

    const initialCategory = window.reportCategory || '';
    const initialReport = window.reportValue || '';

    if (categorySelect) {
        categorySelect.value = initialCategory;

        categorySelect.addEventListener('change', function () {
            populateReports('');
            updateFilters();
        });
    }

    populateReports(initialReport);
    updateFilters();

    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');

    if (dateFrom && dateTo) {

        dateFrom.addEventListener('change', function () {
            if (dateTo.value && dateFrom.value > dateTo.value) {
                dateTo.value = dateFrom.value;
            }
        });

        dateTo.addEventListener('change', function () {
            if (dateFrom.value && dateTo.value < dateFrom.value) {
                dateFrom.value = dateTo.value;
            }
        });
    }

    const printButton = document.getElementById('printReportBtn');

    if (printButton) {
        printButton.addEventListener('click', function () {
            window.print();
        });
    }
});
