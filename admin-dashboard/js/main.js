// =============================================================================
// Smart Campus Admin Dashboard JavaScript
// Handles table filtering, AJAX modals, and Chart.js rendering
// =============================================================================

document.addEventListener('DOMContentLoaded', function () {
    // 1. Table Quick Filter
    const searchInput = document.getElementById('tableSearchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const filter = searchInput.value.toLowerCase();
            const table = document.getElementById('complaintsTable');
            if (!table) return;
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const text = rows[i].textContent.toLowerCase();
                rows[i].style.display = text.indexOf(filter) > -1 ? '' : 'none';
            }
        });
    }

    // 2. Initialize Charts if canvas elements exist
    initDashboardCharts();
});

function initDashboardCharts() {
    // Status breakdown chart
    const statusCanvas = document.getElementById('statusChart');
    if (statusCanvas && window.Chart) {
        const open = parseInt(statusCanvas.dataset.open || 0);
        const assigned = parseInt(statusCanvas.dataset.assigned || 0);
        const inprogress = parseInt(statusCanvas.dataset.inprogress || 0);
        const resolved = parseInt(statusCanvas.dataset.resolved || 0);
        const closed = parseInt(statusCanvas.dataset.closed || 0);

        new Chart(statusCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Open', 'Assigned', 'In Progress', 'Resolved', 'Closed'],
                datasets: [{
                    data: [open, assigned, inprogress, resolved, closed],
                    backgroundColor: ['#f59e0b', '#3b82f6', '#8b5cf6', '#10b981', '#64748b'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // Category breakdown chart
    const categoryCanvas = document.getElementById('categoryChart');
    if (categoryCanvas && window.Chart && window.campusCategoryData) {
        const labels = window.campusCategoryData.map(c => c.name);
        const counts = window.campusCategoryData.map(c => c.count);

        new Chart(categoryCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Complaints by Category',
                    data: counts,
                    backgroundColor: '#1a365d',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
}
