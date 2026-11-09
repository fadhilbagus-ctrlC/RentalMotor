// Tooltip grafik omzet
(function () {
    const chart = document.getElementById('omzetChart');
    const tooltip = document.getElementById('chartTooltip');

    if (!chart || !tooltip) return;

    const hits = chart.querySelectorAll('.chart-hit');

    function showTooltip(hit) {
        tooltip.innerHTML = '';

        const value = document.createElement('strong');
        value.textContent = hit.dataset.value || '';

        const label = document.createElement('span');
        label.textContent = hit.dataset.label || '';

        tooltip.appendChild(value);
        tooltip.appendChild(label);
        tooltip.hidden = false;

        const xPercent = parseFloat(hit.dataset.xpct || '50');
        const yPercent = parseFloat(hit.dataset.ypct || '50');
        const width = chart.clientWidth;
        const half = tooltip.offsetWidth / 2;

        const rawLeft = (xPercent / 100) * width;
        const left = Math.min(width - half - 6, Math.max(half + 6, rawLeft));

        tooltip.style.left = left + 'px';
        tooltip.style.top = Math.max(8, yPercent) + '%';
    }

    function hideTooltip() {
        tooltip.hidden = true;
    }

    hits.forEach(function (hit) {
        hit.addEventListener('mouseenter', function () { showTooltip(hit); });
        hit.addEventListener('mouseleave', hideTooltip);
        hit.addEventListener('focus', function () { showTooltip(hit); });
        hit.addEventListener('blur', hideTooltip);
    });

    chart.addEventListener('mouseleave', hideTooltip);
})();
