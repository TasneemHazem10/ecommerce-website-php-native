<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
init_session();
require_login();

$order_number = $_GET['order'] ?? '';
$order = null;

if ($order_number) {
    try {
        $stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.order_number = ? AND o.user_id = ?");
        $stmt->execute([$order_number, $_SESSION['user_id']]);
        $order = $stmt->fetch();
        
        if ($order) {
            $stmt = $pdo->prepare("SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
            $stmt->execute([$order['id']]);
            $order['items'] = $stmt->fetchAll();
        }
    } catch(PDOException $e) {
        $error = 'Failed to load order details';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - E-Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        </div>
                        
                        <h2 class="mb-3">Order Placed Successfully!</h2>
                        <p class="text-muted mb-4">Thank you for your order. We'll send you an email confirmation shortly.</p>
                        
                        <?php if ($order): ?>
                            <div class="bg-light rounded p-4 mb-4 text-start">
                                <h5 class="mb-3">Order Details</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Order Number:</strong> <?= $order['order_number'] ?></p>
                                        <p class="mb-2"><strong>Date:</strong> <?= date('F j, Y', strtotime($order['created_at'])) ?></p>
                                        <p class="mb-2"><strong>Status:</strong> <span class="badge bg-warning"><?= ucfirst($order['status']) ?></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Total Amount:</strong> <?= format_price($order['total_amount']) ?></p>
                                        <p class="mb-2"><strong>Payment Method:</strong> <?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?></p>
                                        <p class="mb-2"><strong>Shipping Address:</strong> <?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                                    </div>
                                </div>
                                
                                <?php if (!empty($order['items'])): ?>
                                    <h6 class="mt-4 mb-3">Order Items:</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Quantity</th>
                                                    <th>Price</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($order['items'] as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                                    <td><?= $item['quantity'] ?></td>
                                                    <td><?= format_price($item['price']) ?></td>
                                                    <td><?= format_price($item['price'] * $item['quantity']) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-center gap-3">
                            <a href="products.php" class="btn btn-primary">
                                <i class="bi bi-bag"></i> Continue Shopping
                            </a>
                            <a href="orders.php" class="btn btn-outline-secondary">
                                <i class="bi bi-list-ul"></i> View My Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>