<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
init_session();
require_login();

$orders = [];

try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
    
    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
    }
} catch(PDOException $e) {
    $error = 'Failed to load orders';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - E-Store</title>
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
                <h1 class="mb-4">My Orders</h1>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="bi bi-box-seam fs-1 text-muted"></i>
                <h3 class="mt-3">No Orders Yet</h3>
                <p class="text-muted">You haven't placed any orders yet. Start shopping to see your orders here!</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="bi bi-bag"></i> Start Shopping
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="mb-0">Order #<?= $order['order_number'] ?></h6>
                                <small class="text-muted"><?= date('F j, Y, g:i A', strtotime($order['created_at'])) ?></small>
                            </div>
                            <div class="col-md-6 text-end">
                                <span class="badge bg-<?= $order['status'] == 'pending' ? 'warning' : ($order['status'] == 'delivered' ? 'success' : 'info') ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                                <strong class="ms-3"><?= format_price($order['total_amount']) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small class="text-muted">Shipping Address:</small>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Payment Method:</small>
                                <p class="mb-0"><?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?></p>
                            </div>
                        </div>
                        
                        <h6>Order Items:</h6>
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
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>