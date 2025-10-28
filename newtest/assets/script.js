// Order Calculator JavaScript Functions with Bootstrap
let itemCount = 0;

function addItem() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'row mb-3 p-3 border rounded';
    newRow.innerHTML = `
        <div class="col-md-3">
            <label class="form-label">Item Name</label>
            <input type="text" class="form-control" name="name_${itemCount}" placeholder="e.g., Tire" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Price ($)</label>
            <input type="number" class="form-control" name="price_${itemCount}" placeholder="0.00" min="0" step="0.01" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Quantity</label>
            <input type="number" class="form-control" name="quantity_${itemCount}" placeholder="1" min="1" required>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
                <i class="bi bi-trash"></i> Remove
            </button>
        </div>
    `;
    container.appendChild(newRow);
    itemCount++;
    document.getElementById('itemCount').value = itemCount;
}

function removeItem(button) {
    const container = document.getElementById('itemsContainer');
    if (container.children.length > 1) {
        button.closest('.row').remove();
        itemCount--;
        document.getElementById('itemCount').value = itemCount;
    }
}

// Initialize item count on page load
document.addEventListener('DOMContentLoaded', function() {
    itemCount = parseInt(document.getElementById('itemCount').value) || 0;
});
