// Kalkulator harga booking
function hitungHarga() {
    const pickupDate = document.getElementById('pickup_date');
    const returnDate = document.getElementById('return_date');
    const pricePerDay = document.getElementById('price_per_day');

    if (!pickupDate || !returnDate || !pricePerDay) return;

    const pickup = pickupDate.value;
    const returnD = returnDate.value;
    const price = parseInt(pricePerDay.value);

    // Set minimum return date = pickup date + 1
    if (pickup) {
        const nextDay = new Date(pickup);
        nextDay.setDate(nextDay.getDate() + 1);
        returnDate.min = nextDay.toISOString().split('T')[0];
    }

    // Update display pickup date
    const displayPickup = document.getElementById('display_pickup');
    const displayReturn = document.getElementById('display_return');
    const displayDays = document.getElementById('display_days');
    const displayTotal = document.getElementById('display_total');

    if (pickup) {
        const pDate = new Date(pickup);
        if (displayPickup) displayPickup.textContent = pDate.toLocaleDateString('id-ID', {
            day: 'numeric', month: 'short', year: 'numeric'
        });
    } else {
        if (displayPickup) displayPickup.textContent = '-';
    }

    if (returnD) {
        const rDate = new Date(returnD);
        if (displayReturn) displayReturn.textContent = rDate.toLocaleDateString('id-ID', {
            day: 'numeric', month: 'short', year: 'numeric'
        });
    } else {
        if (displayReturn) displayReturn.textContent = '-';
    }

    // Hitung jumlah hari
    if (pickup && returnD) {
        const pDate = new Date(pickup);
        const rDate = new Date(returnD);
        const diffTime = rDate - pDate;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays > 0) {
            const total = diffDays * price;

            if (displayDays) displayDays.textContent = diffDays + ' hari';
            if (displayTotal) displayTotal.textContent = 'Rp ' + total.toLocaleString('id-ID');

            // Enable button
            const btn = document.getElementById('btnBooking');
            if (btn) btn.disabled = false;
        } else {
            if (displayDays) displayDays.textContent = '0 hari';
            if (displayTotal) displayTotal.textContent = 'Rp 0';

            const btn = document.getElementById('btnBooking');
            if (btn) btn.disabled = true;
        }
    } else {
        if (displayDays) displayDays.textContent = '0 hari';
        if (displayTotal) displayTotal.textContent = 'Rp 0';
    }
}

// Filter tabel riwayat booking lewat stat card
function initBookingFilters() {
    const cards = Array.from(document.querySelectorAll('.stat-card[data-filter]'));
    if (!cards.length) return;

    const table = document.getElementById('bookingTable');
    const tbody = table ? table.querySelector('tbody') : null;
    const rows = tbody ? Array.from(tbody.querySelectorAll('tr[data-status]')) : [];
    const emptyRow = tbody ? tbody.querySelector('.filter-empty') : null;
    const info = document.getElementById('filterInfo');

    const filters = {
        all: null,
        pending: ['pending'],
        booked: ['confirmed', 'active'],
        completed: ['completed']
    };
    const labels = {
        all: 'Semua booking',
        pending: 'Menunggu',
        booked: 'Sudah dibooking',
        completed: 'Selesai'
    };

    let currentFilter = 'all';

    function applyFilter(filter) {
        const allowed = filters[filter] ?? null;
        let visible = 0;

        rows.forEach(function(row) {
            const match = !allowed || allowed.indexOf(row.dataset.status) !== -1;
            row.hidden = !match;

            if (match) {
                visible++;
                const noCell = row.cells[0];
                if (noCell) noCell.textContent = visible;
            }
        });

        if (emptyRow) emptyRow.hidden = visible !== 0;

        cards.forEach(function(card) {
            const active = card.dataset.filter === filter;
            card.classList.toggle('is-active', active);
            card.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        if (info) {
            if (!rows.length) {
                info.textContent = 'Belum ada booking.';
            } else if (filter === 'all') {
                info.textContent = 'Menampilkan semua ' + visible + ' booking.';
            } else {
                info.textContent = 'Menampilkan ' + visible + ' booking \u2022 ' + labels[filter] + '.';
            }
        }
    }

    cards.forEach(function(card) {
        card.addEventListener('click', function() {
            const filter = card.dataset.filter;
            const nextFilter = (currentFilter === filter && filter !== 'all') ? 'all' : filter;
            currentFilter = nextFilter;
            applyFilter(nextFilter);

            if (nextFilter !== 'all' && table && window.matchMedia('(max-width: 768px)').matches) {
                const target = table.closest('.table-wrapper') || table;
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    applyFilter('all');
}

// Inisialisasi saat halaman load
document.addEventListener('DOMContentLoaded', function() {
    // Jalankan hitungHarga jika form booking ada
    if (document.getElementById('bookingForm')) {
        hitungHarga();
    }

    initBookingFilters();
});
