<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
init_session();

$categories = [];
$featured_products = [];

try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name LIMIT 6")->fetchAll();
    $featured_products = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.featured = 1 AND p.stock > 0 ORDER BY RAND() LIMIT 8")->fetchAll();
} catch(PDOException $e) {
    $error = 'Failed to load homepage data';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce Store - Premium Products at Great Prices</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <section class="hero-section bg-primary text-white py-5">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <h1 class="display-4 fw-bold mb-3">Welcome to Our Store</h1>
                        <p class="lead mb-4">Discover amazing products at unbeatable prices. Shop with confidence and enjoy secure checkout.</p>
                        <a href="products.php" class="btn btn-light btn-lg">
                            <i class="bi bi-bag"></i> Shop Now
                        </a>
                    </div>
                    <div class="col-lg-6">
                        <div class="text-center">
                            <i class="bi bi-bag-check" style="font-size: 12rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="categories-section py-5">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <h2 class="text-center mb-4">Shop by Category</h2>
                    </div>
                </div>
                
                <div class="row g-4">
                    <?php foreach ($categories as $category): ?>
                    <div class="col-md-4 col-lg-2">
                        <a href="products.php?category=<?= $category['id'] ?>" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm category-card">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="bi bi-tag fs-1 text-primary"></i>
                                    </div>
                                    <h5 class="card-title"><?= htmlspecialchars($category['name']) ?></h5>
                                    <small class="text-muted"><?= htmlspecialchars($category['description']) ?></small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        <section class="featured-section py-5 bg-light">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <h2 class="text-center mb-4">Featured Products</h2>
                    </div>
                </div>
                
                <div class="row g-4">
                    <?php if (empty($featured_products)): ?>
                        <div class="col-12 text-center">
                            <p class="text-muted">No featured products available at the moment.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($featured_products as $product): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="card h-100 product-card">
                                <div class="position-relative">
                                    <?php if ($product['image']): ?>
                                        <img src="uploads/<?= $product['image'] ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>" style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                            <i class="bi bi-image fs-1 text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-warning">
                                            <i class="bi bi-star-fill"></i> Featured
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                    <p class="card-text text-muted small mb-2"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></p>
                                    <p class="card-text"><?= substr(htmlspecialchars($product['description']), 0, 80) ?>...</p>
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h4 class="text-primary mb-0"><?= format_price($product['price']) ?></h4>
                                            <small class="text-muted">Stock: <?= $product['stock'] ?></small>
                                        </div>
                                        <div class="d-grid gap-2">
                                            <a href="product.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary">View Details</a>
                                            <button class="btn btn-primary add-to-cart" data-product-id="<?= $product['id'] ?>" data-product-name="<?= htmlspecialchars($product['name']) ?>" data-price="<?= $product['price'] ?>">
                                                <i class="bi bi-cart-plus"></i> Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="products.php" class="btn btn-outline-primary btn-lg">
                        View All Products <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </section>
        
        <section class="features-section py-5">
            <div class="container">
                <div class="row g-4">
                    <div class="col-md-4 text-center">
                        <i class="bi bi-truck fs-1 text-primary mb-3"></i>
                        <h4>Free Shipping</h4>
                        <p class="text-muted">On orders over $100</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="bi bi-shield-check fs-1 text-primary mb-3"></i>
                        <h4>Secure Payment</h4>
                        <p class="text-muted">100% secure transactions</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="bi bi-arrow-repeat fs-1 text-primary mb-3"></i>
                        <h4>Easy Returns</h4>
                        <p class="text-muted">30-day return policy</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/main.js"></script>
</body>
</html>