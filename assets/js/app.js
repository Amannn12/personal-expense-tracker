document.addEventListener('DOMContentLoaded', function () {
  const canvas = document.getElementById('categoryChart');
  if (canvas && typeof categoryData !== 'undefined' && categoryData.length) {
    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: categoryData.map(item => item.category_name),
        datasets: [{ data: categoryData.map(item => Number(item.total)), borderWidth: 0, hoverOffset: 5 }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 18 } } },
        cutout: '68%'
      }
    });
  }
});