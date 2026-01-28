<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
init_session();

$cart_items = [];
$total_amount = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
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
                $cart_items[] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'image' => $product['image'],
                    'quantity' => $item['quantity'],
                    'stock' => $product['stock'],
                    'subtotal' => $product['price'] * $item['quantity']
                ];
                $total_amount += $product['price'] * $item['quantity'];
            }
        }
    } catch(PDOException $e) {
        $error = 'Failed to load cart data';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $product_id => $quantity) {
            $product_id = intval($product_id);
            $quantity = intval($quantity);
            
            if ($quantity > 0 && isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            } elseif ($quantity == 0) {
                unset($_SESSION['cart'][$product_id]);
            }
        }
        redirect('cart.php?updated=1');
    }
    
    if (isset($_POST['remove_item']) && isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
        unset($_SESSION['cart'][$product_id]);
        redirect('cart.php?removed=1');
    }
    
    if (isset($_POST['clear_cart'])) {
        unset($_SESSION['cart']);
        redirect('cart.php?cleared=1');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - E-Store</title>
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
                <h1 class="mb-4">Shopping Cart</h1>
            </div>
        </div>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Cart updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['removed'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Item removed from cart!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['cleared'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Cart cleared!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (empty($cart_items)): ?>
            <div class="text-center py-5">
                <i class="bi bi-cart-x fs-1 text-muted"></i>
                <h3 class="mt-3">Your cart is empty</h3>
                <p class="text-muted">Add some products to your cart to get started!</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="bi bi-bag"></i> Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="cart.php">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Subtotal</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cart_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($item['image']): ?>
                                                            <img src="uploads/<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="bg-light d-inline-block me-3" style="width: 60px; height: 60px;"></div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <h6 class="mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                                                            <small class="text-muted">Stock: <?= $item['stock'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= format_price($item['price']) ?></td>
                                                <td>
                                                    <input type="number" name="quantities[<?= $item['id'] ?>]" 
                                                           class="form-control form-control-sm" 
                                                           value="<?= $item['quantity'] ?>" 
                                                           min="1" max="<?= $item['stock'] ?>" 
                                                           style="width: 80px;">
                                                </td>
                                                <td><?= format_price($item['subtotal']) ?></td>
                                                <td>
                                                    <button type="submit" name="remove_item" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirm('Remove this item from cart?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <button type="submit" name="clear_cart" class="btn btn-outline-danger" 
                                            onclick="return confirm('Clear entire cart?')">
                                        <i class="bi bi-trash"></i> Clear Cart
                                    </button>
                                    <div>
                                        <button type="submit" name="update_cart" class="btn btn-primary me-2">
                                            <i class="bi bi-arrow-clockwise"></i> Update Cart
                                        </button>
                                        <a href="products.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-bag"></i> Continue Shopping
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
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
                            
                            <?php if (is_logged_in()): ?>
                                <a href="checkout.php" class="btn btn-primary w-100">
                                    <i class="bi bi-credit-card"></i> Proceed to Checkout
                                </a>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <small>Please <a href="login.php">login</a> to proceed with checkout.</small>
                                </div>
                                <a href="login.php" class="btn btn-primary w-100">
                                    <i class="bi bi-person"></i> Login to Checkout
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>