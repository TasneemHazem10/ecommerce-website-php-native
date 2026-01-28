<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_admin();

$stats = [];
try {
    $total_products = $pdo->query("SELECT COUNT(*) as count FROM products")->fetch()['count'];
    $total_categories = $pdo->query("SELECT COUNT(*) as count FROM categories")->fetch()['count'];
    $total_users = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch()['count'];
    $total_orders = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'];
    $pending_orders = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch()['count'];
    $total_revenue = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'")->fetch()['total'] ?? 0;
    
    $recent_orders = $pdo->query("SELECT o.*, u.name as customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll();
    $low_stock_products = $pdo->query("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5")->fetchAll();
    
} catch(PDOException $e) {
    $error = 'Failed to load dashboard data';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="h3 mb-4">Dashboard Overview</h1>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?= $total_products ?></h4>
                                <p class="mb-0">Total Products</p>
                            </div>
                            <i class="bi bi-box fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?= $total_orders ?></h4>
                                <p class="mb-0">Total Orders</p>
                            </div>
                            <i class="bi bi-cart-check fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?= $total_users ?></h4>
                                <p class="mb-0">Customers</p>
                            </div>
                            <i class="bi bi-people fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0"><?= format_price($total_revenue) ?></h4>
                                <p class="mb-0">Total Revenue</p>
                            </div>
                            <i class="bi bi-currency-dollar fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><?= $order['order_number'] ?></td>
                                        <td><?= $order['customer_name'] ?? 'Guest' ?></td>
                                        <td><?= format_price($order['total_amount']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $order['status'] == 'pending' ? 'warning' : ($order['status'] == 'delivered' ? 'success' : 'info') ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Low Stock Alert</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($low_stock_products)): ?>
                            <p class="text-muted">All products are well stocked</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($low_stock_products as $product): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0"><?= $product['name'] ?></h6>
                                            <small class="text-muted">Stock: <?= $product['stock'] ?></small>
                                        </div>
                                        <span class="badge bg-danger">Low</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>