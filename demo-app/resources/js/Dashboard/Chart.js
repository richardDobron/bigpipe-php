export default class Chart {
    draw(values) {
        const chart = document.getElementById('revenue-chart');
        const max = Math.max(...values);

        chart.innerHTML = '';
        values.forEach((value, i) => {
            const bar = document.createElement('div');
            bar.className = 'flex-1';
            bar.style.background = 'linear-gradient(var(--violet), var(--blue))';
            bar.style.borderRadius = '4px 4px 2px 2px';
            bar.style.height = `${Math.max(6, Math.round((value / max) * 100))}%`;
            bar.style.animation = `rise 0.4s ease-out ${i * 0.04}s both`;
            bar.title = String(value);
            chart.appendChild(bar);
        });
    }
}
