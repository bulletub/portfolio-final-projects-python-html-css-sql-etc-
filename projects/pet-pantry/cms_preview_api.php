<?php
// cms_preview_api.php - API endpoint for CMS preview functionality
session_start();
header('Content-Type: application/json');
require_once 'database.php';
require_once 'audit_helper.php';

// Verify admin access
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$stmt = $pdo->prepare("SELECT role_name FROM admin_roles WHERE user_id=?");
$stmt->execute([$userId]);
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('super_admin', $roles) && !in_array('admin_dashboard', $roles)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_carousel':
            $stmt = $pdo->query("
                SELECT * FROM carousel_images 
                WHERE is_active = 1 
                ORDER BY display_order ASC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Fallback to default images if empty
            if (empty($data)) {
                $data = [
                    ['id' => 0, 'image_path' => 'images/bg1.png', 'alt_text' => 'Happy pets enjoying premium food', 'display_order' => 1],
                    ['id' => 0, 'image_path' => 'images/bg2.png', 'alt_text' => 'Nutritious pet food bowl', 'display_order' => 2],
                    ['id' => 0, 'image_path' => 'images/bg3.png', 'alt_text' => 'Healthy pets running outdoors', 'display_order' => 3]
                ];
            }
            
            echo json_encode($data);
            break;

        case 'get_bestsellers':
            // Check if CMS-managed bestsellers exist
            $stmt = $pdo->query("
                SELECT p.*, hs.display_order 
                FROM homepage_sections hs 
                JOIN products p ON hs.product_id = p.id 
                WHERE hs.section_name='bestseller' 
                ORDER BY hs.display_order ASC
                LIMIT 8
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Fallback to automatic bestsellers if none set
            if (empty($data)) {
                $stmt = $pdo->query("
                    SELECT p.id, p.name, p.description, p.price, p.subcategory, p.image, p.stock, p.images,
                           COUNT(o.id) AS total_orders
                    FROM products p
                    LEFT JOIN orders o ON p.id = o.product_id
                    GROUP BY p.id
                    ORDER BY total_orders DESC
                    LIMIT 8
                ");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode($data);
            break;

        case 'get_featured':
            // Check if CMS-managed featured exist
            $stmt = $pdo->query("
                SELECT p.*, hs.display_order 
                FROM homepage_sections hs 
                JOIN products p ON hs.product_id = p.id 
                WHERE hs.section_name='featured' 
                ORDER BY hs.display_order ASC
                LIMIT 8
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Fallback to automatic featured if none set
            if (empty($data)) {
                $stmt = $pdo->query("
                    SELECT p.id, p.name, p.description, p.price, p.subcategory, p.image, p.stock, p.images,
                           COALESCE(AVG(r.rating), 0) AS avg_rating
                    FROM products p
                    LEFT JOIN orders o ON p.id = o.product_id
                    LEFT JOIN order_reviews r ON o.id = r.order_id
                    GROUP BY p.id
                    ORDER BY avg_rating DESC, p.id DESC
                    LIMIT 8
                ");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode($data);
            break;

        case 'get_all_products':
            $stmt = $pdo->query("
                SELECT id, name, price, image, stock 
                FROM products 
                ORDER BY name ASC
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'remove_product':
            // Handle POST request to remove product from section
            $input = json_decode(file_get_contents('php://input'), true);
            $section = $input['section'] ?? '';
            $productId = (int)($input['product_id'] ?? 0);
            
            if ($section && $productId) {
                // Get old data for audit
                $stmt = $pdo->prepare("
                    SELECT * FROM homepage_sections 
                    WHERE section_name = ? AND product_id = ?
                ");
                $stmt->execute([$section, $productId]);
                $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Delete the entry
                $stmt = $pdo->prepare("
                    DELETE FROM homepage_sections 
                    WHERE section_name = ? AND product_id = ?
                ");
                $stmt->execute([$section, $productId]);
                
                // Log audit
                if ($oldData) {
                    logAudit(
                        $pdo, 
                        $userId, 
                        $_SESSION['name'] ?? 'Admin', 
                        'DELETE', 
                        'homepage_sections', 
                        $oldData['id'],
                        $oldData,
                        null,
                        "Removed product ID {$productId} from {$section} section"
                    );
                }
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            }
            break;

        case 'add_product_to_section':
            // Handle POST request to add product to section
            $input = json_decode(file_get_contents('php://input'), true);
            $section = $input['section'] ?? '';
            $productId = (int)($input['product_id'] ?? 0);
            
            if ($section && $productId) {
                // Get max display order
                $stmt = $pdo->prepare("
                    SELECT COALESCE(MAX(display_order), 0) + 1 as next_order 
                    FROM homepage_sections 
                    WHERE section_name = ?
                ");
                $stmt->execute([$section]);
                $nextOrder = $stmt->fetchColumn();
                
                // Check if already exists
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM homepage_sections 
                    WHERE section_name = ? AND product_id = ?
                ");
                $stmt->execute([$section, $productId]);
                
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'error' => 'Product already in section']);
                    break;
                }
                
                // Insert
                $stmt = $pdo->prepare("
                    INSERT INTO homepage_sections (section_name, product_id, display_order) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$section, $productId, $nextOrder]);
                
                // Log audit
                logAudit(
                    $pdo, 
                    $userId, 
                    $_SESSION['name'] ?? 'Admin', 
                    'CREATE', 
                    'homepage_sections', 
                    $pdo->lastInsertId(),
                    null,
                    ['section_name' => $section, 'product_id' => $productId, 'display_order' => $nextOrder],
                    "Added product ID {$productId} to {$section} section"
                );
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            }
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

