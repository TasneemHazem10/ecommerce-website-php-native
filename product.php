<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
init_session();

$product = null;
$related_products = [];
$error = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('products.php');
}

$product_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.stock > 0");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        $error = 'Product not found or out of stock';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? AND stock > 0 ORDER BY RAND() LIMIT 4");
        $stmt->execute([$product['category_id'], $product_id]);
        $related_products = $stmt->fetchAll();
    }
} catch(PDOException $e) {
    $error = 'Failed to load product data';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name'] ?? 'Product') ?> - E-Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container py-4">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php elseif ($product): ?>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                    <?php if ($product['category_name']): ?>
                        <li class="breadcrumb-item"><a href="products.php?category=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
                </ol>
            </nav>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <?php if ($product['image']): ?>
                        <img src="uploads/<?= $product['image'] ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($product['name']) ?>">
                    <?php else: ?>
                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 400px;">
                            <i class="bi bi-image fs-1 text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <?php if ($product['featured']): ?>
                            <span class="badge bg-warning me-2">
                                <i class="bi bi-star-fill"></i> Featured
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($product['stock'] < 10): ?>
                            <span class="badge bg-danger">Low Stock</span>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="h2 mb-3"><?= htmlspecialchars($product['name']) ?></h1>
                    
                    <p class="text-muted mb-3">
                        <i class="bi bi-tag"></i> <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                    </p>
                    
                    <div class="d-flex align-items-center mb-3">
                        <h2 class="text-primary mb-0 me-3"><?= format_price($product['price']) ?></h2>
                        <span class="badge bg-success">In Stock (<?= $product['stock'] ?>)</span>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Description</h5>
                        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    </div>
                    
                    <div class="d-flex gap-3 mb-4">
                        <div class="input-group" style="max-width: 150px;">
                            <button class="btn btn-outline-secondary" type="button" id="decrease-qty">-</button>
                            <input type="number" class="form-control text-center" id="quantity" value="1" min="1" max="<?= $product['stock'] ?>">
                            <button class="btn btn-outline-secondary" type="button" id="increase-qty">+</button>
                        </div>
                        
                        <button class="btn btn-primary add-to-cart flex-grow-1" 
                                data-product-id="<?= $product['id'] ?>" 
                                data-product-name="<?= htmlspecialchars($product['name']) ?>" 
                                data-price="<?= $product['price'] ?>">
                            <i class="bi bi-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Product Details</h6>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">SKU</small>
                                    <p class="mb-0">#PRD<?= str_pad($product['id'], 5, '0', STR_PAD_LEFT) ?></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Category</small>
                                    <p class="mb-0"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Stock</small>
                                    <p class="mb-0"><?= $product['stock'] ?> units</p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Added</small>
                                    <p class="mb-0"><?= date('M j, Y', strtotime($product['created_at'])) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($related_products)): ?>
                <section class="mt-5">
                    <h3 class="mb-4">Related Products</h3>
                    <div class="row g-4">
                        <?php foreach ($related_products as $related): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="card h-100 product-card">
                                <div class="position-relative">
                                    <?php if ($related['image']): ?>
                                        <img src="uploads/<?= $related['image'] ?>" class="card-img-top" alt="<?= htmlspecialchars($related['name']) ?>" style="height: 150px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                                            <i class="bi bi-image fs-1 text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title"><?= htmlspecialchars($related['name']) ?></h6>
                                    <h5 class="text-primary"><?= format_price($related['price']) ?></h5>
                                    <a href="product.php?id=<?= $related['id'] ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>