const searchInput = document.getElementById('search-input');
const tableBody = document.getElementById('client-table-body');

searchInput.addEventListener('input', function () {
    const term = searchInput.value;
    fetch('{{ base_path }}/admin/search?query=' + encodeURIComponent(term))
        .then(res => {
            if (!res.ok) throw new Error('Search failed');
            return res.json();
        })
        .then(data => {
            tableBody.innerHTML = '';

            if (data.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="no-results">No clients found.</td>
                    </tr>`;
                return;
            }

            data.forEach(client => {
                tableBody.innerHTML += `
                    <tr>
                        <td>${escapeHtml(client.phone)}</td>
                        <td>${escapeHtml(client.event_type)}</td>
                        <td>${escapeHtml(client.date)}</td>
                        <td>${escapeHtml(client.guests)}</td>
                        <td>$${escapeHtml(client.budget.toLocaleString())}</td>
                        <td>${escapeHtml(client.status)}</td>
                        <td><a href="{{ base_path }}/admin/client/${client.id}">View Details</a></td>
                    </tr>
                `;
            });
        })
        .catch(err => console.error(err));
});

//to avoid xss
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
