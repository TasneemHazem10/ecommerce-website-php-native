<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
init_session();
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND stock > 0");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found or out of stock']);
                exit;
            }
            
            if ($quantity > $product['stock']) {
                echo json_encode(['success' => false, 'message' => 'Requested quantity exceeds available stock']);
                exit;
            }
            
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            if (isset($_SESSION['cart'][$product_id])) {
                $new_quantity = $_SESSION['cart'][$product_id]['quantity'] + $quantity;
                if ($new_quantity > $product['stock']) {
                    echo json_encode(['success' => false, 'message' => 'Cannot add more items than available stock']);
                    exit;
                }
                $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'quantity' => $quantity,
                    'added_at' => time()
                ];
            }
            
            $cart_count = 0;
            foreach ($_SESSION['cart'] as $item) {
                $cart_count += $item['quantity'];
            }
            
            echo json_encode(['success' => true, 'message' => 'Product added to cart!', 'cart_count' => $cart_count]);
            
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit;
            }
            
            if ($quantity > $product['stock']) {
                echo json_encode(['success' => false, 'message' => 'Requested quantity exceeds available stock']);
                exit;
            }
            
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$product_id]);
            } else {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            }
            
            echo json_encode(['success' => true, 'message' => 'Cart updated!']);
            
        } elseif ($action === 'remove') {
            unset($_SESSION['cart'][$product_id]);
            echo json_encode(['success' => true, 'message' => 'Item removed from cart!']);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
    
    exit;
}
?>