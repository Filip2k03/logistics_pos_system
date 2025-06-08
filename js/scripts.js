document.addEventListener('DOMContentLoaded', function() {
    const weightInput = document.getElementById('weight_kg');
    const pricePerKgInput = document.getElementById('price_per_kg');
    const totalAmountInput = document.getElementById('total_amount');
    const originRegionSelect = document.getElementById('origin_region'); // Assuming this exists on create_voucher.php

    // Function to calculate total amount
    function calculateTotal() {
        const weight = parseFloat(weightInput.value);
        const pricePerKg = parseFloat(pricePerKgInput.value);

        if (!isNaN(weight) && !isNaN(pricePerKg)) {
            const total = weight * pricePerKg;
            totalAmountInput.value = total.toFixed(2); // Format to 2 decimal places
        } else {
            totalAmountInput.value = '0.00';
        }
    }

    // Add event listeners for input changes
    if (weightInput && pricePerKgInput && totalAmountInput) {
        weightInput.addEventListener('input', calculateTotal);
        pricePerKgInput.addEventListener('input', calculateTotal);
    }

    // Fetch price per kg based on origin region
    if (originRegionSelect && pricePerKgInput) {
        originRegionSelect.addEventListener('change', function() {
            const selectedOption = originRegionSelect.options[originRegionSelect.selectedIndex];
            const price = selectedOption.dataset.priceperkg; // Get price from data-priceperkg attribute

            if (price) {
                pricePerKgInput.value = parseFloat(price).toFixed(2);
                calculateTotal(); // Recalculate total if price changes
            }
        });

        // Trigger change on load to set initial price
        originRegionSelect.dispatchEvent(new Event('change'));
    }

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000); // 5000 milliseconds = 5 seconds
    });
});

// Function to confirm stock status update (replaces alert/confirm)
function showConfirmModal(callback) {
    const modalHtml = `
        <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-3 shadow">
                    <div class="modal-header bg-primary text-white border-bottom-0 rounded-top-3">
                        <h5 class="modal-title" id="confirmModalLabel">Confirm Update</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p>Are you sure you want to update the stock status?</p>
                    </div>
                    <div class="modal-footer flex-column border-top-0 px-4 pb-3">
                        <button type="button" class="btn btn-lg btn-primary w-100 mx-0 mb-2 rounded-pill" id="confirmYes">Yes</button>
                        <button type="button" class="btn btn-lg btn-light w-100 mx-0 rounded-pill" data-bs-dismiss="modal">No</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if any
    const existingModal = document.getElementById('confirmModal');
    if (existingModal) {
        existingModal.remove();
    }

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    confirmModal.show();

    document.getElementById('confirmYes').onclick = function() {
        confirmModal.hide();
        callback(true);
    };

    document.getElementById('confirmModal').addEventListener('hidden.bs.modal', function () {
        // Callback with false if modal is dismissed without "Yes"
        if (!document.getElementById('confirmYes').dataset.clicked) {
             callback(false);
        }
        document.getElementById('confirmYes').removeAttribute('data-clicked'); // Reset for next time
    }, { once: true });

    document.getElementById('confirmYes').addEventListener('click', function() {
        this.dataset.clicked = 'true'; // Mark that "Yes" was clicked
    });
}