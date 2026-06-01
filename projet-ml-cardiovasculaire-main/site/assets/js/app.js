document.addEventListener('DOMContentLoaded', () => {
  const canvas = document.getElementById('demoChart');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const values = [32, 48, 57, 74, 69, 85];
  const labels = ['18-29', '30-39', '40-49', '50-59', '60-69', '70+'];
  const width = canvas.width;
  const height = canvas.height;
  const padding = 42;
  const chartWidth = width - padding * 2;
  const chartHeight = height - padding * 2;

  ctx.clearRect(0, 0, width, height);
  ctx.strokeStyle = '#8fa1d8';
  ctx.lineWidth = 1;

  for (let i = 0; i <= 4; i++) {
    const y = padding + (chartHeight / 4) * i;
    ctx.beginPath();
    ctx.moveTo(padding, y);
    ctx.lineTo(width - padding, y);
    ctx.stroke();
  }

  const max = Math.max(...values);
  const barWidth = chartWidth / values.length - 16;

  values.forEach((value, index) => {
    const x = padding + index * (barWidth + 16) + 8;
    const barHeight = (value / max) * (chartHeight - 20);
    const y = height - padding - barHeight;

    ctx.fillStyle = '#4c63d2';
    ctx.fillRect(x, y, barWidth, barHeight);

    ctx.fillStyle = '#23314d';
    ctx.font = "12px 'Inter', Arial";
    ctx.fillText(labels[index], x - 2, height - 16);
    ctx.fillText(String(value), x + 8, y - 8);
  });

  ctx.fillStyle = '#23314d';
  ctx.font = "bold 14px 'Inter', Arial";
  ctx.fillText('Exemple de répartition du risque par tranche d\'âge', padding, 24);
});
