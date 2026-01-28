<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_admin();

$orders = [];

try {
    $stmt = $pdo->query("SELECT o.*, u.name as customer_name, u.email as customer_email FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
    $orders = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Failed to load orders';
}

if (isset($_GET['update_status']) && isset($_GET['status']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $status = sanitize_input($_GET['status']);
    
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($status, $valid_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            redirect('orders.php?updated=1');
        } catch(PDOException $e) {
            $error = 'Failed to update order status';
        }
    }
}

if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $order_id = $_GET['view'];
    
    try {
        $stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, u.address as customer_address FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $stmt->execute([$order_id]);
        $order_details = $stmt->fetch();
        
        if ($order_details) {
            $stmt = $pdo->prepare("SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
            $stmt->execute([$order_id]);
            $order_details['items'] = $stmt->fetchAll();
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
    <title>Orders Management - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="h3 mb-4">Orders Management</h1>
            </div>
        </div>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Order status updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (isset($order_details)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Order Details - #<?= $order_details['order_number'] ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <p><strong>Name:</strong> <?= htmlspecialchars($order_details['customer_name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($order_details['customer_email']) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($order_details['customer_phone'] ?? 'N/A') ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Order Information</h6>
                            <p><strong>Order Number:</strong> <?= $order_details['order_number'] ?></p>
                            <p><strong>Date:</strong> <?= date('F j, Y, g:i A', strtotime($order_details['created_at'])) ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?= $order_details['status'] == 'pending' ? 'warning' : ($order_details['status'] == 'delivered' ? 'success' : 'info') ?>">
                                    <?= ucfirst($order_details['status']) ?>
                                </span>
                            </p>
                            <p><strong>Total Amount:</strong> <?= format_price($order_details['total_amount']) ?></p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6>Shipping Address</h6>
                        <p><?= nl2br(htmlspecialchars($order_details['shipping_address'])) ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h6>Update Status</h6>
                        <div class="btn-group" role="group">
                            <a href="orders.php?update_status=pending&order_id=<?= $order_details['id'] ?>" class="btn btn-outline-warning <?= $order_details['status'] == 'pending' ? 'active' : '' ?>">Pending</a>
                            <a href="orders.php?update_status=processing&order_id=<?= $order_details['id'] ?>" class="btn btn-outline-info <?= $order_details['status'] == 'processing' ? 'active' : '' ?>">Processing</a>
                            <a href="orders.php?update_status=shipped&order_id=<?= $order_details['id'] ?>" class="btn btn-outline-primary <?= $order_details['status'] == 'shipped' ? 'active' : '' ?>">Shipped</a>
                            <a href="orders.php?update_status=delivered&order_id=<?= $order_details['id'] ?>" class="btn btn-outline-success <?= $order_details['status'] == 'delivered' ? 'active' : '' ?>">Delivered</a>
                            <a href="orders.php?update_status=cancelled&order_id=<?= $order_details['id'] ?>" class="btn btn-outline-danger <?= $order_details['status'] == 'cancelled' ? 'active' : '' ?>">Cancelled</a>
                        </div>
                    </div>
                    
                    <h6>Order Items</h6>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_details['items'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td><?= format_price($item['price']) ?></td>
                                    <td><?= format_price($item['price'] * $item['quantity']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3">Total:</th>
                                    <th><?= format_price($order_details['total_amount']) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <a href="orders.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Orders
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No orders found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?= $order['order_number'] ?></td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($order['customer_name']) ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($order['customer_email']) ?></small>
                                            </div>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                        <td><?= format_price($order['total_amount']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $order['status'] == 'pending' ? 'warning' : ($order['status'] == 'delivered' ? 'success' : 'info') ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="orders.php?view=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>