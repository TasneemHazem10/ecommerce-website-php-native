<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_admin();

$categories = [];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    
    if (empty($name)) {
        $error = 'Category name is required';
    } else {
        try {
            if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description, $_GET['edit']]);
                $success = 'Category updated successfully!';
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                $success = 'Category added successfully!';
            }
            
            $_POST = [];
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Category name already exists';
            } else {
                $error = 'Failed to save category. Please try again.';
            }
        }
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $category_id = $_GET['delete'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $product_count = $stmt->fetch()['count'];
        
        if ($product_count > 0) {
            $error = 'Cannot delete category. It contains ' . $product_count . ' product(s).';
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            
            redirect('categories.php?deleted=1');
        }
    } catch(PDOException $e) {
        $error = 'Failed to delete category';
    }
}

try {
    $categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count FROM categories c ORDER BY c.name")->fetchAll();
} catch(PDOException $e) {
    $error = 'Failed to load categories';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Admin Panel</title>
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
                <h1 class="h3 mb-4">Categories Management</h1>
            </div>
        </div>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Category deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add New Category</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="categories.php">
                            <div class="mb-3">
                                <label for="name" class="form-label">Category Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required value="<?= $_POST['name'] ?? '' ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?= $_POST['description'] ?? '' ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle"></i> Add Category
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Categories</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-tags fs-1"></i>
                                <p class="mb-0 mt-2">No categories found</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Products</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td>
                                                <strong><?= $category['name'] ?></strong>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= $category['description'] ?: 'No description' ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= $category['product_count'] ?></span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= date('M j, Y', strtotime($category['created_at'])) ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="editCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>', '<?= htmlspecialchars($category['description']) ?>')">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <a href="categories.php?delete=<?= $category['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this category?')" <?= $category['product_count'] > 0 ? 'disabled' : '' ?>>
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(id, name, description) {
            document.getElementById('name').value = name;
            document.getElementById('description').value = description;
            document.querySelector('.card-header h5').textContent = 'Edit Category';
            document.querySelector('form').action = 'categories.php?edit=' + id;
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>