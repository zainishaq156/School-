// dashboard.js
// ChartJS Attendance Pie Chart - for student dashboard
window.addEventListener('DOMContentLoaded', function () {
    const chartElem = document.getElementById('attendanceChart');
    if (chartElem && typeof Chart !== 'undefined') {
        // Data values should be injected via PHP
        const present = chartElem.dataset.present ? parseInt(chartElem.dataset.present) : 0;
        const absent = chartElem.dataset.absent ? parseInt(chartElem.dataset.absent) : 0;
        const data = {
            labels: ['Present', 'Absent'],
            datasets: [{
                data: [present, absent],
                backgroundColor: ['#2b55c0', '#e33619']
            }]
        };
        new Chart(chartElem.getContext('2d'), {
            type: 'pie',
            data: data,
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                }
            }
        });
    }
});
