// ===== Attendance toggle visual state + mark-all shortcuts =====
document.addEventListener('change', function (e) {
    if (e.target.matches('.status-toggle input[type="radio"]')) {
        const row = e.target.closest('.status-toggle');
        row.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
        e.target.closest('.toggle-btn').classList.add('active');
    }
});

function markAll(status) {
    document.querySelectorAll(`.status-toggle input[value="${status}"]`).forEach(input => {
        input.checked = true;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
}

// ===== Export any container as a JPG screenshot =====
function exportAsImage(elementId, filename) {
    const el = document.getElementById(elementId);
    if (!el) return;
    html2canvas(el, { backgroundColor: '#ffffff', scale: 2 }).then(canvas => {
        const link = document.createElement('a');
        link.download = filename + '.jpg';
        link.href = canvas.toDataURL('image/jpeg', 0.95);
        link.click();
    });
}

// ===== Custom confirmation modal (replaces the ugly native browser confirm()) =====
// Usage: add data-confirm="Your message" to any <a href="..."> link.
document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-confirm]');
    if (!trigger) return;
    e.preventDefault();
    const message = trigger.getAttribute('data-confirm');
    const href = trigger.getAttribute('href');
    showConfirmModal(message, () => { window.location.href = href; });
});

function showConfirmModal(message, onConfirm) {
    const overlay = document.createElement('div');
    overlay.className = 'confirm-overlay';
    overlay.innerHTML = `
        <div class="confirm-box">
            <p class="confirm-message">${message}</p>
            <div class="confirm-actions">
                <button type="button" class="confirm-btn cancel">Cancel</button>
                <button type="button" class="confirm-btn ok">Confirm</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    overlay.querySelector('.cancel').onclick = () => overlay.remove();
    overlay.querySelector('.ok').onclick = () => { overlay.remove(); onConfirm(); };
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.remove();
    });
}