<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
init_session();
require_login();

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    redirect('cart.php');
}

$cart_items = [];
$total_amount = 0;

try {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $products_by_id = [];
    foreach ($products as $product) {
        $products_by_id[$product['id']] = $product;
    }
    
    foreach ($_SESSION['cart'] as $product_id => $item) {
        if (isset($products_by_id[$product_id])) {
            $product = $products_by_id[$product_id];
            
            if ($item['quantity'] > $product['stock']) {
                $_SESSION['cart'][$product_id]['quantity'] = $product['stock'];
            }
            
            $cart_items[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $_SESSION['cart'][$product_id]['quantity'],
                'subtotal' => $product['price'] * $_SESSION['cart'][$product_id]['quantity']
            ];
            $total_amount += $product['price'] * $_SESSION['cart'][$product_id]['quantity'];
        }
    }
    
    $user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user->execute([$_SESSION['user_id']]);
    $user_info = $user->fetch();
    
} catch(PDOException $e) {
    $error = 'Failed to load checkout data';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = sanitize_input($_POST['shipping_address']);
    $payment_method = sanitize_input($_POST['payment_method']);
    
    if (empty($shipping_address)) {
        $error = 'Shipping address is required';
    } elseif (empty($payment_method)) {
        $error = 'Payment method is required';
    } else {
        try {
            $pdo->beginTransaction();
            
            $order_number = 'ORD' . time() . rand(1000, 9999);
            
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, total_amount, shipping_address, payment_method, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $order_number, $total_amount, $shipping_address, $payment_method]);
            $order_id = $pdo->lastInsertId();
            
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
                
                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['id']]);
            }
            
            $pdo->commit();
            
            unset($_SESSION['cart']);
            
            redirect('order-success.php?order=' . $order_number);
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error = 'Failed to place order. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - E-Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Checkout</h1>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" action="checkout.php">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Shipping Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user_info['name']) ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user_info['email']) ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" value="<?= htmlspecialchars($user_info['phone'] ?? '') ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label for="shipping_address" class="form-label">Shipping Address *</label>
                                <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?= htmlspecialchars($user_info['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Payment Method</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cash_on_delivery" checked>
                                    <label class="form-check-label" for="cod">
                                        <strong>Cash on Delivery</strong>
                                        <p class="text-muted small mb-0">Pay when you receive your order</p>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="card" value="credit_card" disabled>
                                    <label class="form-check-label" for="card">
                                        <strong>Credit/Debit Card</strong>
                                        <p class="text-muted small mb-0">Coming soon...</p>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="mb-3">Order Items</h6>
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <div>
                                            <small><?= htmlspecialchars($item['name']) ?></small>
                                            <br><small class="text-muted">Qty: <?= $item['quantity'] ?></small>
                                        </div>
                                        <small><?= format_price($item['subtotal']) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span><?= format_price($total_amount) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping:</span>
                                <span>Free</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax:</span>
                                <span>$0.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong class="text-primary"><?= format_price($total_amount) ?></strong>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" class="text-decoration-none">Terms & Conditions</a>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i> Place Order
                            </button>
                            
                            <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2">
                                <i class="bi bi-arrow-left"></i> Back to Cart
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>