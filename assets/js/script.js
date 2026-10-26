/**
 * Student Expense Tracker - Main JavaScript
 * Handles Sidebar toggle, Client-side validation, Charts, and Confirmation Dialogs
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('appSidebar');
    let backdrop = document.getElementById('sidebarBackdrop');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            if (sidebar.classList.contains('show')) {
                if (!backdrop) {
                    backdrop = document.createElement('div');
                    backdrop.id = 'sidebarBackdrop';
                    backdrop.className = 'sidebar-backdrop';
                    document.body.appendChild(backdrop);
                    backdrop.addEventListener('click', function () {
                        sidebar.classList.remove('show');
                        backdrop.remove();
                        backdrop = null;
                    });
                }
            } else if (backdrop) {
                backdrop.remove();
                backdrop = null;
            }
        });
    }

    // 2. Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });

    // 3. Client-side Form Validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            // Password confirmation check if present
            const pwd = form.querySelector('input[name="password"]');
            const confirmPwd = form.querySelector('input[name="confirm_password"]');
            if (pwd && confirmPwd && pwd.value !== confirmPwd.value) {
                confirmPwd.setCustomValidity('Passwords do not match');
            } else if (confirmPwd) {
                confirmPwd.setCustomValidity('');
            }

            // Amount field positive validation
            const amountInput = form.querySelector('input[type="number"][name="amount"], input[type="number"][name="budget_amount"]');
            if (amountInput) {
                if (parseFloat(amountInput.value) <= 0 || isNaN(parseFloat(amountInput.value))) {
                    amountInput.setCustomValidity('Amount must be greater than zero');
                } else {
                    amountInput.setCustomValidity('');
                }
            }

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // 4. Delete Confirmation Hook
    const deleteBtns = document.querySelectorAll('.btn-confirm-delete');
    deleteBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const itemType = this.getAttribute('data-item-name') || 'record';
            if (!confirm(`Are you sure you want to delete this ${itemType}? This action cannot be undone.`)) {
                e.preventDefault();
            }
        });
    });
});

/**
 * Helper to initialize Dashboard Charts via Chart.js
 */
function initDashboardCharts(categoryData, monthlyTrendData) {
    // 1. Category Breakdown Doughnut Chart
    const catCanvas = document.getElementById('categoryPieChart');
    if (catCanvas && categoryData && categoryData.labels.length > 0) {
        new Chart(catCanvas, {
            type: 'doughnut',
            data: {
                labels: categoryData.labels,
                datasets: [{
                    data: categoryData.values,
                    backgroundColor: categoryData.colors.length > 0 ? categoryData.colors : [
                        '#f59e0b', '#3b82f6', '#8b5cf6', '#10b981', '#ef4444',
                        '#ec4899', '#6366f1', '#06b6d4', '#14b8a6', '#64748b'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 12,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                const val = ctx.raw || 0;
                                return ' ' + ctx.label + ': ₹' + Number(val).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 2. Monthly Inflow vs Outflow Bar / Line Chart
    const trendCanvas = document.getElementById('monthlyTrendChart');
    if (trendCanvas && monthlyTrendData && monthlyTrendData.labels.length > 0) {
        new Chart(trendCanvas, {
            type: 'bar',
            data: {
                labels: monthlyTrendData.labels,
                datasets: [
                    {
                        label: 'Total Income',
                        data: monthlyTrendData.income,
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                        borderColor: '#10b981',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Total Expenses',
                        data: monthlyTrendData.expense,
                        backgroundColor: 'rgba(239, 68, 68, 0.85)',
                        borderColor: '#ef4444',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 12, font: { size: 12 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ' ' + ctx.dataset.label + ': ₹' + Number(ctx.raw || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '₹' + value;
                            }
                        },
                        grid: {
                            color: '#f1f5f9'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
}
