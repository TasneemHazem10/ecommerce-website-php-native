<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_admin();

$product = null;
$error = '';
$success = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('products.php');
}

$product_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        redirect('products.php');
    }
    
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    
} catch(PDOException $e) {
    $error = 'Failed to load product data';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $stock = intval($_POST['stock']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    if (empty($name) || empty($price)) {
        $error = 'Product name and price are required';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0';
    } elseif ($stock < 0) {
        $error = 'Stock cannot be negative';
    } else {
        try {
            $image = $product['image'];
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = $_FILES['image']['type'];
                
                if (!in_array($file_type, $allowed_types)) {
                    $error = 'Only JPG, PNG, GIF, and WebP images are allowed';
                } else {
                    if ($product['image'] && file_exists('../uploads/' . $product['image'])) {
                        unlink('../uploads/' . $product['image']);
                    }
                    
                    $file_name = time() . '_' . basename($_FILES['image']['name']);
                    $upload_path = '../uploads/' . $file_name;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image = $file_name;
                    } else {
                        $error = 'Failed to upload image';
                    }
                }
            }
            
            if (empty($error)) {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, stock = ?, image = ?, featured = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $category_id, $stock, $image, $featured, $product_id]);
                
                $success = 'Product updated successfully!';
                
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
            }
        } catch(PDOException $e) {
            $error = 'Failed to update product. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin Panel</title>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Edit Product</h1>
                    <a href="products.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Products
                    </a>
                </div>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST" action="edit-product.php?id=<?= $product_id ?>" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required value="<?= htmlspecialchars($product['name']) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required value="<?= $product['price'] ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="stock" class="form-label">Stock Quantity</label>
                                        <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?= $product['stock'] ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" <?= $product['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                            <?= $category['name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="featured" name="featured" <?= $product['featured'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="featured">
                                        Featured Product
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="form-text">JPG, PNG, GIF, WebP (Max 5MB)</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="preview-container bg-light d-flex align-items-center justify-content-center" style="height: 200px; border: 2px dashed #dee2e6; border-radius: 8px;">
                                    <?php if ($product['image']): ?>
                                        <img src="../uploads/<?= $product['image'] ?>" class="img-fluid" style="max-height: 100%; object-fit: contain;" alt="Current product image">
                                    <?php else: ?>
                                        <div class="text-center text-muted">
                                            <i class="bi bi-image fs-1"></i>
                                            <p class="mb-0 mt-2">No Image</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between gap-2">
                        <a href="products.php?delete=<?= $product['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">
                            <i class="bi bi-trash"></i> Delete Product
                        </a>
                        <div class="d-flex gap-2">
                            <a href="products.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Product
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.querySelector('.preview-container');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="img-fluid" style="max-height: 100%; object-fit: contain;">`;
                }
                reader.readAsDataURL(file);
            } else {
                <?php if ($product['image']): ?>
                preview.innerHTML = '<img src="../uploads/<?= $product['image'] ?>" class="img-fluid" style="max-height: 100%; object-fit: contain;" alt="Current product image">';
                <?php else: ?>
                preview.innerHTML = `
                    <div class="text-center text-muted">
                        <i class="bi bi-image fs-1"></i>
                        <p class="mb-0 mt-2">No Image</p>
                    </div>
                `;
                <?php endif; ?>
            }
        });
    </script>
</body>
</html>