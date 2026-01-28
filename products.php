<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
init_session();

$products = [];
$categories = [];
$current_category = '';
$search = '';

if (isset($_GET['category']) && is_numeric($_GET['category'])) {
    $current_category = $_GET['category'];
}

if (isset($_GET['search'])) {
    $search = sanitize_input($_GET['search']);
}

try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    
    $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock > 0";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($current_category)) {
        $sql .= " AND p.category_id = ?";
        $params[] = $current_category;
    }
    
    $sql .= " ORDER BY p.featured DESC, p.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = 'Failed to load products';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - E-Store</title>
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
                <h1 class="mb-4">Our Products</h1>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="products.php">
                            <div class="mb-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" value="<?= $search ?>" placeholder="Search products...">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" <?= $current_category == $category['id'] ? 'selected' : '' ?>>
                                            <?= $category['name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                            <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">Clear</a>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <?php if (empty($products)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-search fs-1 text-muted"></i>
                        <h4 class="mt-3">No products found</h4>
                        <p class="text-muted">Try adjusting your filters or search terms</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($products as $product): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 product-card">
                                <?php if ($product['featured']): ?>
                                    <div class="position-absolute top-0 start-0 m-2 z-1">
                                        <span class="badge bg-warning">
                                            <i class="bi bi-star-fill"></i> Featured
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="position-relative">
                                    <?php if ($product['image']): ?>
                                        <img src="uploads/<?= $product['image'] ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>" style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                            <i class="bi bi-image fs-1 text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($product['stock'] < 10): ?>
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <span class="badge bg-danger">Low Stock</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                    <p class="card-text text-muted small mb-2"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></p>
                                    <p class="card-text"><?= substr(htmlspecialchars($product['description']), 0, 100) ?>...</p>
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
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>