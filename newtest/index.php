<?php
/**
 * Order Calculator - Professional Order Management System
 * Calculates discounts and totals for automotive parts orders
 */

require_once 'config.php';

// Configuration
define('ITEM_DISCOUNT_THRESHOLD', 5);
define('ITEM_DISCOUNT_RATE', 0.10);
define('BULK_DISCOUNT_THRESHOLD', 500);
define('BULK_DISCOUNT_RATE', 0.05);

/**
 * Calculate order total with discounts
 * @param array $items Array of items with name, price, and quantity
 * @return array Order calculation results
 */
function calculateOrderTotal($items) {
    if (empty($items) || !is_array($items)) {
        return [
            'error' => 'Invalid items provided',
            'subtotal' => 0,
            'item_discounts' => 0,
            'bulk_discount' => 0,
            'total_discounts' => 0,
            'final_total' => 0
        ];
    }
    
    $subtotal = 0;
    $itemDiscounts = 0;
    $processedItems = [];
    
    // Calculate subtotal and apply per-item discounts
    foreach ($items as $item) {
        // Validate item data
        if (!isset($item['name']) || !isset($item['price']) || !isset($item['quantity'])) {
            continue;
        }
        
        $price = floatval($item['price']);
        $quantity = intval($item['quantity']);
        
        if ($price <= 0 || $quantity <= 0) {
            continue;
        }
        
        $itemSubtotal = $price * $quantity;
        $discount = 0;
        
        // Apply 10% discount if quantity is 5 or more for this specific item
        if ($quantity >= ITEM_DISCOUNT_THRESHOLD) {
            $discount = $itemSubtotal * ITEM_DISCOUNT_RATE;
            $itemDiscounts += $discount;
            $itemSubtotal -= $discount;
        }
        
        $subtotal += $itemSubtotal;
        
        $processedItems[] = [
            'name' => htmlspecialchars($item['name']),
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $itemSubtotal,
            'discount' => $discount,
            'has_discount' => $quantity >= ITEM_DISCOUNT_THRESHOLD
        ];
    }
    
    // Apply bulk order discount (5% if subtotal before tax exceeds $500)
    $bulkDiscount = 0;
    if ($subtotal > BULK_DISCOUNT_THRESHOLD) {
        $bulkDiscount = $subtotal * BULK_DISCOUNT_RATE;
        $subtotal -= $bulkDiscount;
    }
    
    return [
        'items' => $processedItems,
        'subtotal' => $subtotal,
        'item_discounts' => $itemDiscounts,
        'bulk_discount' => $bulkDiscount,
        'total_discounts' => $itemDiscounts + $bulkDiscount,
        'final_total' => $subtotal,
        'has_bulk_discount' => $bulkDiscount > 0
    ];
}

// Load items from database
$items_result = $conn->query("SELECT * FROM items ORDER BY type, name");
$db_items = $items_result->fetch_all(MYSQLI_ASSOC);

// Process form submission
$result = null;
$order = [];

if ($_POST) {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'quantity_') === 0) {
            $item_id = substr($key, 9);
            $quantity = intval($value);
            if ($quantity > 0) {
                // Find matching item from database
                foreach ($db_items as $db_item) {
                    if ($db_item['id'] == $item_id) {
                        $order[] = [
                            'name' => $db_item['name'],
                            'price' => $db_item['price'],
                            'quantity' => $quantity
                        ];
                        break;
                    }
                }
            }
        }
    }
    $result = calculateOrderTotal($order);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaito Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <!-- Header Card -->
                    <div class="card mb-4">
                        <div class="card-header text-center">
                            <h1 class="h3 mb-2">Auto Parts Order System</h1>
                            <p class="mb-0">Select quantities for the items you wish to purchase</p>
                        </div>
                    </div>
                    
                    <!-- Items Grid + Cart (two-column on large screens) -->
                    <form method="POST" id="orderForm">
                        <div class="row g-4 mb-4">
                            <div class="col-12 col-lg-9">
                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                                    <?php foreach ($db_items as $item): ?>
                                    <div class="col">
                                        <!-- make the whole card act as the item container -->
                                        <div class="card item-card" data-item-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>" data-price="<?php echo $item['price']; ?>">
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                 class="card-img-top item-image" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <div class="card-body">
                                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                                <p class="item-type mb-2"><?php echo htmlspecialchars($item['type']); ?></p>
                                                <p class="card-text mb-3">
                                                    <strong>Price: $<?php echo number_format($item['price'], 2); ?></strong>
                                                </p>
                                                <!-- small helper text -->
                                                <p class="mb-0 text-muted" style="font-size:.85rem;">Click card to focus quantity</p>
                                            </div>

                                            <!-- footer holds the quantity control and Add button -->
                                            <div class="card-footer d-flex align-items-center justify-content-between">
                                                <div class="input-group me-2" style="max-width:170px;">
                                                    <label class="input-group-text" for="quantity_<?php echo $item['id']; ?>">Qty</label>
                                                    <input type="number" 
                                                           class="form-control quantity-input" 
                                                           id="quantity_<?php echo $item['id']; ?>"
                                                           name="quantity_<?php echo $item['id']; ?>"
                                                           min="0"
                                                           value="0"
                                                           aria-label="Quantity for <?php echo htmlspecialchars($item['name']); ?>">
                                                </div>
                                                <div>
                                                    <button type="button" class="btn btn-sm btn-outline-dark add-to-cart" data-item-id="<?php echo $item['id']; ?>">
                                                        <i class="bi bi-cart-plus"></i> Add
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="text-center mt-4 mb-4 d-lg-none">
                                    <!-- keep a visible calculate button on small screens -->
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="bi bi-calculator"></i> Calculate Order
                                    </button>
                                </div>
                            </div>

                            <!-- Cart column -->
                            <div class="col-12 col-lg-3">
                                <div class="cart-panel">
                                    <div class="card cart-card">
                                        <div class="card-header d-flex align-items-center justify-content-between">
                                            <strong>Cart</strong>
                                            <button type="button" id="clearCartBtn" class="btn btn-sm btn-outline-secondary">Clear</button>
                                        </div>
                                        <div class="card-body cart-body">
                                            <div id="cartItemsContainer" class="list-group mb-3">
                                                <div class="cart-empty">Your cart is empty</div>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <div>Subtotal:</div>
                                                <div id="cartSubtotal">$0.00</div>
                                            </div>
                                            <div class="d-grid gap-2">
                                                <button type="submit" class="btn btn-primary">Calculate Order</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <?php if ($result): ?>
                    <!-- Results Card -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Order Summary</h5>
                            
                            <?php if (isset($result['error'])): ?>
                                <div class="alert alert-danger">
                                    <?php echo $result['error']; ?>
                                </div>
                            <?php else: ?>
                                <!-- Order Items Table -->
                                <div class="table-responsive mb-4">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item</th>
                                                <th>Price</th>
                                                <th>Qty</th>
                                                <th>Subtotal</th>
                                                <th>Discount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($result['items'] as $item): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo $item['name']; ?></strong>
                                                        <?php if ($item['has_discount']): ?>
                                                            <span class="badge bg-success discount-badge ms-2">10% OFF</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                    <td><?php echo $item['quantity']; ?></td>
                                                    <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                                                    <td>
                                                        <?php if ($item['discount'] > 0): ?>
                                                            <span class="text-success">-$<?php echo number_format($item['discount'], 2); ?></span>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Totals Section -->
                                <div class="total-section rounded p-4">
                                    <div class="row mb-2">
                                        <div class="col-8">Item Discounts (10% on 5+ qty):</div>
                                        <div class="col-4 text-end text-success">-$<?php echo number_format($result['item_discounts'], 2); ?></div>
                                    </div>
                                    
                                    <?php if ($result['has_bulk_discount']): ?>
                                        <div class="row mb-2">
                                            <div class="col-8">Bulk Order Discount (5% on $500+):</div>
                                            <div class="col-4 text-end text-warning">-$<?php echo number_format($result['bulk_discount'], 2); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="row mb-2">
                                        <div class="col-8">Total Discounts:</div>
                                        <div class="col-4 text-end text-success">-$<?php echo number_format($result['total_discounts'], 2); ?></div>
                                    </div>
                                    
                                    <hr class="my-3">
                                    <div class="row">
                                        <div class="col-8"><strong>Final Total:</strong></div>
                                        <div class="col-4 text-end"><strong>$<?php echo number_format($result['final_total'], 2); ?></strong></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Cart logic - wrapped in DOMContentLoaded to ensure DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Cart system initializing...');
        const cart = {}; // { id: { id, name, price, qty } }

        function formatCurrency(v) {
            return '$' + v.toFixed(2);
        }

        function renderCart() {
            const container = document.getElementById('cartItemsContainer');
            container.innerHTML = '';
            const keys = Object.keys(cart);
            if (keys.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'cart-empty';
                empty.textContent = 'Your cart is empty';
                container.appendChild(empty);
                document.getElementById('cartSubtotal').textContent = formatCurrency(0);
                return;
            }
            let subtotal = 0;
            keys.forEach(id => {
                const it = cart[id];
                const itemEl = document.createElement('div');
                itemEl.className = 'list-group-item d-flex justify-content-between align-items-center';
                itemEl.innerHTML = `
                    <div>
                        <div class="fw-bold">${escapeHtml(it.name)}</div>
                        <div class="text-muted small">${it.qty} &times; ${formatCurrency(it.price)}</div>
                    </div>
                    <div class="text-end">
                        <div class="mb-2">${formatCurrency(it.qty * it.price)}</div>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Qty controls">
                            <button type="button" class="btn btn-outline-secondary btn-decrease" data-id="${id}">-</button>
                            <button type="button" class="btn btn-outline-secondary btn-increase" data-id="${id}">+</button>
                            <button type="button" class="btn btn-outline-danger btn-remove" data-id="${id}"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                `;
                container.appendChild(itemEl);
                subtotal += it.qty * it.price;
            });
            document.getElementById('cartSubtotal').textContent = formatCurrency(subtotal);
        }

        function escapeHtml(text) {
            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Add or update cart item from UI (reads quantity input)
        function addToCartFromCard(id) {
            const input = document.getElementById('quantity_' + id);
            let qty = parseInt(input.value, 10) || 0;
            if (qty <= 0) {
                qty = 1;
                input.value = 1;
            }
            const card = document.querySelector('.card.item-card[data-item-id="' + id + '"]');
            const name = card.getAttribute('data-name');
            const price = parseFloat(card.getAttribute('data-price')) || 0;
            if (cart[id]) {
                cart[id].qty = cart[id].qty + qty;
            } else {
                cart[id] = { id, name, price, qty };
            }
            // sync quantity input with cart total
            input.value = cart[id].qty;
            renderCart();
        }

        function syncInputWithCart(id) {
            const input = document.getElementById('quantity_' + id);
            if (!input) return;
            const val = parseInt(input.value, 10) || 0;
            if (val <= 0) {
                // if input set to 0, remove from cart
                delete cart[id];
            } else {
                const card = document.querySelector('.card.item-card[data-item-id="' + id + '"]');
                const name = card.getAttribute('data-name');
                const price = parseFloat(card.getAttribute('data-price')) || 0;
                cart[id] = { id, name, price, qty: val };
            }
            renderCart();
        }

        // Event delegation for add buttons
        document.querySelectorAll('.add-to-cart').forEach(btn=>{
            btn.addEventListener('click', function(e){
                e.stopPropagation();
                const id = this.getAttribute('data-item-id');
                console.log('Add button clicked for item:', id);
                addToCartFromCard(id);
            });
        });
        console.log('Event listeners attached to', document.querySelectorAll('.add-to-cart').length, 'add buttons');

        // Quantity inputs update cart on change
        document.querySelectorAll('.quantity-input').forEach(inp=>{
            inp.addEventListener('change', function(e){
                e.stopPropagation();
                const id = this.id.replace('quantity_','');
                syncInputWithCart(id);
            });
            // prevent card click from firing when interacting
            inp.addEventListener('click', function(e){
                e.stopPropagation();
            });
            inp.addEventListener('focus', function(e){
                e.stopPropagation();
            });
        });

        // Cart button handlers (delegated)
        document.getElementById('cartItemsContainer').addEventListener('click', function(e){
            const tgt = e.target;
            if (tgt.closest('.btn-increase')) {
                const id = tgt.closest('.btn-increase').getAttribute('data-id');
                cart[id].qty += 1;
                document.getElementById('quantity_' + id).value = cart[id].qty;
            } else if (tgt.closest('.btn-decrease')) {
                const id = tgt.closest('.btn-decrease').getAttribute('data-id');
                cart[id].qty = Math.max(0, cart[id].qty - 1);
                if (cart[id].qty === 0) delete cart[id];
                const input = document.getElementById('quantity_' + id);
                if (input) input.value = cart[id] ? cart[id].qty : 0;
            } else if (tgt.closest('.btn-remove')) {
                const id = tgt.closest('.btn-remove').getAttribute('data-id');
                delete cart[id];
                const input = document.getElementById('quantity_' + id);
                if (input) input.value = 0;
            } else {
                return;
            }
            renderCart();
        });

        // Clear cart
        document.getElementById('clearCartBtn').addEventListener('click', function(){
            Object.keys(cart).forEach(id=>{
                const input = document.getElementById('quantity_' + id);
                if (input) input.value = 0;
                delete cart[id];
            });
            renderCart();
        });

        // Initial render
        renderCart();

        // Preserve existing card click behavior: focus & increment when card clicked
        document.querySelectorAll('.card.item-card').forEach(function(card){
            card.addEventListener('click', function(e){
                if (e.target.closest('input, label, button, a')) return;
                var id = this.getAttribute('data-item-id');
                var input = document.getElementById('quantity_' + id);
                if (input) {
                    input.focus();
                    if (parseInt(input.value, 10) === 0) {
                        input.value = 1;
                    }
                    // Keep cart in sync
                    syncInputWithCart(id);
                }
            });
        });

        // Make sure cart stays in sync if user navigates quantities before adding
        document.getElementById('orderForm').addEventListener('submit', function(){
            // At submit, ensure all quantity inputs reflect cart (they already do since we sync on changes).
            // Nothing extra required here.
        });

    }); // End DOMContentLoaded
    </script>
</body>
</html>