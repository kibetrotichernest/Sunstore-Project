<?php
/**
 * Sunstore Industries - Functions File
 * Contains all database operations and utility functions
 */

// Database connection function with error handling
function db_connect() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

/* ==================== CATEGORY FUNCTIONS ==================== */

/**
 * Get all main product categories
 * @return array List of categories
 */
function get_categories() {
    try {
        $conn = db_connect();
        $sql = "SELECT id, name, slug FROM categories WHERE parent_id = 0 AND status = 1 ORDER BY name";
        $result = $conn->query($sql);
        
        $categories = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    } catch (Exception $e) {
        error_log("Error in get_categories: " . $e->getMessage());
        return [];
    }
}

/**
 * Get categories with product counts
 * @return array List of categories with product counts
 */
function get_categories_with_count() {
    try {
        $conn = db_connect();
        $sql = "SELECT c.id, c.name, c.slug, COUNT(pc.product_id) as product_count 
                FROM categories c
                LEFT JOIN product_categories pc ON c.id = pc.category_id
                LEFT JOIN products p ON pc.product_id = p.id AND p.status = 1
                WHERE c.parent_id = 0 AND c.status = 1
                GROUP BY c.id
                ORDER BY c.name";
        
        $result = $conn->query($sql);
        $categories = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['product_count'] = (int)($row['product_count'] ?? 0);
                $categories[] = $row;
            }
        }
        return $categories;
    } catch (Exception $e) {
        error_log("Error in get_categories_with_count: " . $e->getMessage());
        return [];
    }
}

/**
 * Get main navigation categories
 * @return array List of categories for main nav
 */
function get_main_categories() {
    try {
        $conn = db_connect();
        $sql = "SELECT id, name, slug FROM categories WHERE show_in_nav = 1 AND status = 1 ORDER BY nav_order";
        $result = $conn->query($sql);
        
        $categories = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    } catch (Exception $e) {
        error_log("Error in get_main_categories: " . $e->getMessage());
        return [];
    }
}

/**
 * Get products by category
 * @param int $category_id Category ID
 * @param int $limit Number of products to return
 * @return array List of products
 */
function get_category_products($category_id, $limit = 6) {
    try {
        $conn = db_connect();
        $sql = "SELECT p.id, p.name, p.slug, p.image 
                FROM products p
                JOIN product_categories pc ON p.id = pc.product_id
                WHERE pc.category_id = ? AND p.status = 1
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ii", $category_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    } catch (Exception $e) {
        error_log("Error in get_category_products: " . $e->getMessage());
        return [];
    }
}

/* ==================== PROJECT FUNCTIONS ==================== */

/**
 * Check if a table exists in the database
 * @param string $table_name Name of the table to check
 * @return bool True if table exists, false otherwise
 */
if (!function_exists('table_exists')) {
    function table_exists($table_name) {
        try {
            $conn = db_connect();
            $result = $conn->query("SHOW TABLES LIKE '$table_name'");
            return $result && $result->num_rows > 0;
        } catch (Exception $e) {
            error_log("Error checking table existence: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Get all projects with error handling
 */
function get_all_projects() {
    try {
        $conn = db_connect();
        
        if (!table_exists('projects')) {
            error_log("Projects table does not exist");
            return get_sample_projects();
        }
        
        $query = "SELECT 
                    id, 
                    title, 
                    description, 
                    category, 
                    location, 
                    status, 
                    image, 
                    video, 
                    specs, 
                    date_completed as date, 
                    size,
                    created_at
                  FROM projects 
                  ORDER BY created_at DESC";
        
        error_log("Executing query: " . $query);
        $result = $conn->query($query);
        
        if (!$result) {
            error_log("Project query failed: " . $conn->error);
            return get_sample_projects();
        }
        
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['specs'])) {
                $specs = json_decode($row['specs'], true);
                $row['specs'] = (json_last_error() === JSON_ERROR_NONE) ? $specs : [$row['specs']];
            } else {
                $row['specs'] = [];
            }
            
            $projects[] = $row;
        }
        
        error_log("Retrieved " . count($projects) . " projects from database");
        return $projects;
    } catch (Exception $e) {
        error_log("Error in get_all_projects: " . $e->getMessage());
        return get_sample_projects();
    }
}

/**
 * Get project gallery images
 */
function get_project_gallery($project_id) {
    try {
        if (!table_exists('project_gallery')) {
            return [];
        }
        
        $conn = db_connect();
        $query = "SELECT image FROM project_gallery WHERE project_id = ? ORDER BY sort_order";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $gallery = [];
        while ($row = $result->fetch_assoc()) {
            $gallery[] = $row['image'];
        }
        
        return $gallery;
    } catch (Exception $e) {
        error_log("Error in get_project_gallery: " . $e->getMessage());
        return [];
    }
}

/**
 * Get project testimonial
 */
function get_project_testimonial($project_id) {
    try {
        if (!table_exists('project_testimonials')) {
            return null;
        }
        
        $conn = db_connect();
        $query = "SELECT * FROM project_testimonials WHERE project_id = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    } catch (Exception $e) {
        error_log("Error in get_project_testimonial: " . $e->getMessage());
        return null;
    }
}

/**
 * Get sample projects data for fallback
 */
function get_sample_projects() {
    return [
        [
            'id' => 1,
            'title' => 'Residential Solar Installation',
            'description' => '5kW solar system installation for a family home in Karen',
            'category' => 'Residential',
            'location' => 'Nairobi',
            'status' => 'Completed',
            'date' => '2023-05-15',
            'size' => 5.00,
            'image' => 'project1.jpg',
            'video' => 'https://www.youtube.com/embed/EXAMPLE1',
            'specs' => [
                ['name' => 'Solar Panels', 'value' => '12 x 415W'],
                ['name' => 'Inverter', 'value' => '5kVA Hybrid'],
                ['name' => 'Batteries', 'value' => '2 x 200Ah']
            ],
            'gallery' => ['project1-1.jpg', 'project1-2.jpg'],
            'testimonial' => [
                'client' => 'John Doe',
                'position' => 'Homeowner',
                'content' => 'Excellent service and installation quality'
            ]
        ],
        [
            'id' => 2,
            'title' => 'Commercial Solar Solution',
            'description' => '20kW solar installation for a beachfront hotel',
            'category' => 'Commercial',
            'location' => 'Mombasa',
            'status' => 'Completed',
            'date' => '2023-07-20',
            'size' => 20.00,
            'image' => 'project2.jpg',
            'video' => 'https://www.youtube.com/embed/EXAMPLE2',
            'specs' => [
                ['name' => 'Solar Panels', 'value' => '48 x 415W'],
                ['name' => 'Inverter', 'value' => '20kVA Three Phase'],
                ['name' => 'Batteries', 'value' => '8 x 200Ah']
            ],
            'gallery' => ['project2-1.jpg', 'project2-2.jpg'],
            'testimonial' => [
                'client' => 'Jane Smith',
                'position' => 'Hotel Manager',
                'content' => 'Reduced our energy costs by 60%'
            ]
        ]
    ];
}

/**
 * Get distinct project categories
 */
function get_project_categories() {
    try {
        $conn = db_connect();
        
        if (!table_exists('projects')) {
            return ['Residential', 'Commercial', 'Agricultural', 'Institutional'];
        }
        
        $query = "SELECT DISTINCT category FROM projects WHERE category IS NOT NULL";
        $result = $conn->query($query);
        
        if (!$result) {
            return ['Residential', 'Commercial', 'Agricultural', 'Institutional'];
        }
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
        
        return !empty($categories) ? $categories : ['Residential', 'Commercial', 'Agricultural', 'Institutional'];
    } catch (Exception $e) {
        error_log("Error in get_project_categories: " . $e->getMessage());
        return ['Residential', 'Commercial', 'Agricultural', 'Institutional'];
    }
}

/**
 * Get distinct project locations
 */
function get_project_locations() {
    try {
        $conn = db_connect();
        
        if (!table_exists('projects')) {
            return ['Nairobi', 'Mombasa', 'Nakuru', 'Kisumu'];
        }
        
        $query = "SELECT DISTINCT location FROM projects WHERE location IS NOT NULL";
        $result = $conn->query($query);
        
        if (!$result) {
            return ['Nairobi', 'Mombasa', 'Nakuru', 'Kisumu'];
        }
        
        $locations = [];
        while ($row = $result->fetch_assoc()) {
            $locations[] = $row['location'];
        }
        
        return !empty($locations) ? $locations : ['Nairobi', 'Mombasa', 'Nakuru', 'Kisumu'];
    } catch (Exception $e) {
        error_log("Error in get_project_locations: " . $e->getMessage());
        return ['Nairobi', 'Mombasa', 'Nakuru', 'Kisumu'];
    }
}

/**
 * Get project details by ID
 */
if (!function_exists('get_project_details')) {
    function get_project_details($project_id) {
        try {
            $conn = db_connect();
            
            if (!is_numeric($project_id) || $project_id <= 0) {
                throw new Exception("Invalid project ID: " . $project_id);
            }
            
            if (!table_exists('projects')) {
                $sample = get_sample_projects();
                foreach ($sample as $project) {
                    if ($project['id'] == $project_id) {
                        return $project;
                    }
                }
                return null;
            }
            
            $query = "SELECT 
                        id, 
                        title, 
                        description, 
                        category, 
                        location, 
                        status, 
                        image, 
                        video, 
                        specs, 
                        date_completed as date, 
                        size
                      FROM projects 
                      WHERE id = ?";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return null;
            }
            
            $row = $result->fetch_assoc();
            
            $specs = [];
            if (!empty($row['specs'])) {
                $specs = json_decode($row['specs'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $specs = [$row['specs']];
                }
            }
            
            $project = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'category' => $row['category'],
                'location' => $row['location'],
                'status' => $row['status'],
                'date' => $row['date'],
                'size' => $row['size'],
                'image' => $row['image'],
                'video' => $row['video'],
                'specs' => $specs,
                'gallery' => get_project_gallery($project_id),
                'testimonial' => get_project_testimonial($project_id)
            ];
            
            return $project;
        } catch (Exception $e) {
            error_log("Error in get_project_details: " . $e->getMessage());
            return null;
        }
    }
}

/**
 * Get related projects
 */
function get_related_projects($current_id, $category, $limit = 3) {
    try {
        $conn = db_connect();
        
        if (!table_exists('projects')) {
            $sample = get_sample_projects();
            $related = array_filter($sample, function($p) use ($current_id, $category) {
                return $p['id'] != $current_id && $p['category'] == $category;
            });
            return array_slice($related, 0, $limit);
        }
        
        $projects = [];
        
        $query = "SELECT 
                    id, 
                    title, 
                    image, 
                    location, 
                    category 
                  FROM projects 
                  WHERE id != ? AND category = ? 
                  ORDER BY date_completed DESC 
                  LIMIT ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("isi", $current_id, $category, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        
        if (count($projects) < $limit) {
            $remaining = $limit - count($projects);
            $query = "SELECT 
                        id, 
                        title, 
                        image, 
                        location, 
                        category 
                      FROM projects 
                      WHERE id != ? 
                      ORDER BY date_completed DESC 
                      LIMIT ?";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("ii", $current_id, $remaining);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $projects[] = $row;
            }
        }
        
        return $projects;
    } catch (Exception $e) {
        error_log("Error in get_related_projects: " . $e->getMessage());
        return [];
    }
}

/**
 * Get latest projects for a parent category
 */
function get_latest_projects($pdo, $limit = 4) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM projects ORDER BY date_completed DESC LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching latest projects: " . $e->getMessage());
        return [];
    }
}

/* ==================== PRODUCT FUNCTIONS ==================== */

/**
 * Get total number of active products
 */
function get_total_products() {
    try {
        $conn = db_connect();
        $sql = "SELECT COUNT(*) as total FROM products WHERE status = 1";
        $result = $conn->query($sql);
        
        if ($result) {
            $row = $result->fetch_assoc();
            return (int)$row['total'];
        }
        return 0;
    } catch (Exception $e) {
        error_log("Error in get_total_products: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get all products with pagination
 */
function get_all_products($page = 1, $per_page = 12) {
    try {
        $conn = db_connect();
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT p.*, 
                       GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as category_names,
                       b.name as brand_name 
                FROM products p
                LEFT JOIN product_categories pc ON p.id = pc.product_id
                LEFT JOIN categories c ON pc.category_id = c.id
                LEFT JOIN brands b ON p.brand_id = b.id
                WHERE p.status = 1
                GROUP BY p.id
                ORDER BY p.created_at DESC
                LIMIT ?, ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ii", $offset, $per_page);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    } catch (Exception $e) {
        error_log("Error in get_all_products: " . $e->getMessage());
        return [];
    }
}

/**
 * Get filtered products
 */
function get_filtered_products($category_id = null, $brand_id = null, $price_range = null, $page = 1, $per_page = 12) {
    try {
        $conn = db_connect();
        $offset = ($page - 1) * $per_page;
        
        $where = ["p.status = 1"];
        $params = [];
        $types = '';
        $joins = [
            "LEFT JOIN product_categories pc ON p.id = pc.product_id",
            "LEFT JOIN categories c ON pc.category_id = c.id",
            "LEFT JOIN brands b ON p.brand_id = b.id"
        ];
        
        if ($category_id) {
            $where[] = "pc.category_id = ?";
            $params[] = $category_id;
            $types .= 'i';
        }
        
        if ($brand_id) {
            $where[] = "p.brand_id = ?";
            $params[] = $brand_id;
            $types .= 'i';
        }
        
        if ($price_range && $price_range != 'all') {
            list($min, $max) = explode('-', $price_range);
            if ($max) {
                $where[] = "p.price BETWEEN ? AND ?";
                $params[] = $min;
                $params[] = $max;
                $types .= 'ii';
            } else {
                $where[] = "p.price >= ?";
                $params[] = $min;
                $types .= 'i';
            }
        }
        
        $where_clause = implode(' AND ', $where);
        $join_clause = implode(' ', $joins);
        
        $sql = "SELECT p.*, 
                       GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as category_names,
                       b.name as brand_name 
                FROM products p
                $join_clause
                WHERE $where_clause
                GROUP BY p.id
                ORDER BY p.created_at DESC
                LIMIT ?, ?";
        
        $params[] = $offset;
        $params[] = $per_page;
        $types .= 'ii';
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    } catch (Exception $e) {
        error_log("Error in get_filtered_products: " . $e->getMessage());
        return [];
    }
}

/**
 * Count filtered products
 */
function count_filtered_products($category_id = null, $brand_id = null, $price_range = null) {
    try {
        $conn = db_connect();
        
        $where = ["p.status = 1"];
        $params = [];
        $types = '';
        $joins = [];
        
        if ($category_id) {
            $joins[] = "JOIN product_categories pc ON p.id = pc.product_id";
            $where[] = "pc.category_id = ?";
            $params[] = $category_id;
            $types .= 'i';
        }
        
        if ($brand_id) {
            $where[] = "p.brand_id = ?";
            $params[] = $brand_id;
            $types .= 'i';
        }
        
        if ($price_range && $price_range != 'all') {
            list($min, $max) = explode('-', $price_range);
            if ($max) {
                $where[] = "p.price BETWEEN ? AND ?";
                $params[] = $min;
                $params[] = $max;
                $types .= 'ii';
            } else {
                $where[] = "p.price >= ?";
                $params[] = $min;
                $types .= 'i';
            }
        }
        
        $where_clause = implode(' AND ', $where);
        $join_clause = implode(' ', $joins);
        
        $sql = "SELECT COUNT(DISTINCT p.id) as total 
                FROM products p
                $join_clause
                WHERE $where_clause";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (int)$row['total'];
    } catch (Exception $e) {
        error_log("Error in count_filtered_products: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get featured products
 */
function get_featured_products($limit = 3) {
    try {
        $conn = db_connect();
        $sql = "SELECT p.id, p.name, p.price, p.discount, p.image 
                FROM products p
                WHERE p.featured = 1 AND p.status = 1
                ORDER BY p.created_at DESC
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    } catch (Exception $e) {
        error_log("Error in get_featured_products: " . $e->getMessage());
        return [];
    }
}

/**
 * Get products with special offers/discounts
 */
function get_special_offers($limit = 3) {
    try {
        $conn = db_connect();
        $sql = "SELECT p.id, p.name, p.price, p.discount, p.image 
                FROM products p
                WHERE p.discount > 0 AND p.status = 1
                ORDER BY p.discount DESC
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $offers = [];
        while ($row = $result->fetch_assoc()) {
            $offers[] = $row;
        }
        return $offers;
    } catch (Exception $e) {
        error_log("Error in get_special_offers: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all active brands
 */
function get_brands() {
    try {
        $conn = db_connect();
        $sql = "SELECT id, name FROM brands WHERE status = 1 ORDER BY name";
        $result = $conn->query($sql);
        
        $brands = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $brands[] = $row;
            }
        }
        return $brands;
    } catch (Exception $e) {
        error_log("Error in get_brands: " . $e->getMessage());
        return [];
    }
}

/**
 * Get category name by ID
 */
function get_category_name($id) {
    try {
        $conn = db_connect();
        $sql = "SELECT name FROM categories WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['name'] ?? '';
    } catch (Exception $e) {
        error_log("Error in get_category_name: " . $e->getMessage());
        return '';
    }
}

/**
 * Get brand name by ID
 */
function get_brand_name($id) {
    try {
        $conn = db_connect();
        $sql = "SELECT name FROM brands WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['name'] ?? '';
    } catch (Exception $e) {
        error_log("Error in get_brand_name: " . $e->getMessage());
        return '';
    }
}

/* ==================== UTILITY FUNCTIONS ==================== */

/**
 * Get product category links for footer
 */
function get_product_links() {
    return [
        ['name' => 'Solar Panels', 'url' => 'category.php?type=solar-panels'],
        ['name' => 'Solar Inverters', 'url' => 'category.php?type=solar-inverters'],
        ['name' => 'Solar Batteries', 'url' => 'category.php?type=solar-batteries'],
        ['name' => 'Solar Water Heaters', 'url' => 'category.php?type=solar-water-heaters'],
        ['name' => 'Solar Outdoor Lighting', 'url' => 'category.php?type=solar-lighting'],
        ['name' => 'Solar PV Accessories', 'url' => 'category.php?type=solar-accessories']
    ];
}

/**
 * Get customer information links for footer
 */
function get_customer_links() {
    return [
        ['name' => 'My Account', 'url' => 'account.php'],
        ['name' => 'My Wishlist', 'url' => 'wishlist.php'],
        ['name' => 'How to Place Order', 'url' => 'how-to-order.php'],
        ['name' => 'Payment & Delivery', 'url' => 'payment-delivery.php'],
        ['name' => 'Terms & Conditions', 'url' => 'terms.php'],
        ['name' => 'Cookies Policy', 'url' => 'cookies.php'],
        ['name' => 'Order Tracking', 'url' => 'track-order.php'],
        ['name' => 'Warranty & Support', 'url' => 'warranty.php'],
        ['name' => 'Return & Refund', 'url' => 'returns.php'],
        ['name' => 'Customer Feedback', 'url' => 'feedback.php']
    ];
}

/* ==================== CART & ORDER FUNCTIONS ==================== */

/**
 * Get cart items count
 */
function get_cart_count() {
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        return count($_SESSION['cart']);
    }
    return 0;
}

/**
 * Calculate cart total
 */
function get_cart_total() {
    try {
        $conn = db_connect();
        $total = 0;
        
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            $product_ids = array_keys($_SESSION['cart']);
            
            if (!empty($product_ids)) {
                $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
                $sql = "SELECT id, price, discount FROM products WHERE id IN ($placeholders)";
                $stmt = $conn->prepare($sql);
                
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $types = str_repeat('i', count($product_ids));
                $stmt->bind_param($types, ...$product_ids);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($product = $result->fetch_assoc()) {
                    $quantity = $_SESSION['cart'][$product['id']];
                    $price = $product['price'] * (1 - ($product['discount']/100));
                    $total += $price * $quantity;
                }
            }
        }
        
        return $total;
    } catch (Exception $e) {
        error_log("Error in get_cart_total: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get category by ID
 */
function get_category_by_id($category_id) {
    $conn = db_connect();
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Count products in category
 */
function count_products_in_category($category_id) {
    $conn = db_connect();
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM products 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

/**
 * Get products by category with pagination
 */
function get_products_by_category($category_id, $page = 1, $per_page = 12) {
    $offset = ($page - 1) * $per_page;
    $conn = db_connect();
    $stmt = $conn->prepare("
        SELECT * FROM products 
        WHERE id = ?
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("iii", $category_id, $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

/**
 * Get subcategories for a parent category
 */
function get_subcategories($parent_id) {
    $conn = db_connect();
    
    $stmt = $conn->prepare("
        SELECT c.*, COUNT(p.id) as product_count 
        FROM categories c
        LEFT JOIN products p ON p.id = c.id
        WHERE c.parent_id = ?
        GROUP BY c.id
        ORDER BY c.name
    ");
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $subcategories = [];
    
    while ($row = $result->fetch_assoc()) {
        $subcategories[] = $row;
    }
    
    return $subcategories;

}
// Blog functions
function get_blog_posts($limit = 10, $offset = 0, $category = null, $search = null) {
    global $pdo;
    
    $query = "SELECT p.*, c.name as category_name, u.username as author_name 
              FROM blog_posts p 
              LEFT JOIN blog_categories c ON p.category_id = c.id 
              LEFT JOIN users u ON p.author_id = u.id 
              WHERE p.published_at <= NOW()";
    
    $params = [];
    
    if ($category) {
        $query .= " AND (c.slug = :category OR c.id = :category_id)";
        $params[':category'] = $category;
        $params[':category_id'] = is_numeric($category) ? $category : null;
    }
    
    if ($search) {
        $query .= " AND (p.title LIKE :search OR p.content LIKE :search OR p.excerpt LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    $query .= " ORDER BY p.published_at DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    
    $stmt = $pdo->prepare($query);
    
    foreach ($params as $key => &$val) {
        if (is_int($val)) {
            $stmt->bindParam($key, $val, PDO::PARAM_INT);
        } else {
            $stmt->bindParam($key, $val);
        }
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_blog_post_by_slug($slug) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, u.username as author_name 
                          FROM blog_posts p 
                          LEFT JOIN blog_categories c ON p.category_id = c.id 
                          LEFT JOIN users u ON p.author_id = u.id 
                          WHERE p.slug = ? AND p.published_at <= NOW()");
    $stmt->execute([$slug]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($post) {
        // Increment view count
        $pdo->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?")->execute([$post['id']]);
        
        // Get tags
        $stmt = $pdo->prepare("SELECT t.name, t.slug FROM blog_tags t 
                              JOIN blog_post_tags pt ON t.id = pt.tag_id 
                              WHERE pt.post_id = ?");
        $stmt->execute([$post['id']]);
        $post['tags'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $post;
}

function get_blog_categories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM blog_categories ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_featured_blog_posts($limit = 3) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM blog_posts 
                          WHERE is_featured = TRUE AND published_at <= NOW() 
                          ORDER BY published_at DESC LIMIT ?");
    $stmt->bindParam(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_related_blog_posts($post_id, $category_id, $limit = 3) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM blog_posts 
                          WHERE id != ? AND category_id = ? AND published_at <= NOW() 
                          ORDER BY published_at DESC LIMIT ?");
    $stmt->bindParam(1, $post_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $category_id, PDO::PARAM_INT);
    $stmt->bindParam(3, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function format_blog_content($content) {
    if (empty($content)) {
        return '<p class="text-muted">No content available for this post.</p>';
    }
    
    // Basic formatting
    $content = htmlspecialchars_decode($content);
    $content = nl2br($content);
    
    // Make images responsive
    $content = preg_replace(
        '/<img(.*?)>/i',
        '<img$1 class="img-fluid" loading="lazy">',
        $content
    );
    
    return $content;
}

/**
 * Get all blog posts (for count)
 */
function get_all_blog_posts() {
    global $pdo;
    $stmt = $pdo->query("SELECT id FROM blog_posts WHERE published_at <= NOW()");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get post count for a category
 */
function get_category_post_count($category_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM blog_posts 
                          WHERE category_id = ? AND published_at <= NOW()");
    $stmt->execute([$category_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

/**
 * Get popular posts by view count
 */
function get_popular_blog_posts($limit = 3) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, title, slug, featured_image, views 
                          FROM blog_posts 
                          WHERE published_at <= NOW() 
                          ORDER BY views DESC 
                          LIMIT ?");
    $stmt->bindParam(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get popular tags with count
 */
function get_popular_tags($limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT t.id, t.name, COUNT(pt.post_id) as count 
                          FROM blog_tags t
                          JOIN blog_post_tags pt ON t.id = pt.tag_id
                          GROUP BY t.id
                          ORDER BY count DESC
                          LIMIT ?");
    $stmt->bindParam(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function authenticate_user($email, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }
        return false;
    } catch (PDOException $e) {
        // Log error
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get product by id
 */
function get_product_by_id($product_id) {
    global $pdo; // Access the PDO connection
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Product fetch error: " . $e->getMessage());
        return false;
    }
}

function create_order($order_data) {
    global $conn;
    
    // Check if connection exists
    if (!$conn) {
        throw new Exception("Database connection not established");
    }
    
    try {
        $conn->beginTransaction();
        
        // Insert order
        $stmt = $conn->prepare("INSERT INTO orders (
            customer_id, first_name, last_name, email, phone, 
            address, city, county, payment_method, payment_status, 
            order_total, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        
        $stmt->execute([
            $order_data['customer_id'] ?? null,
            $order_data['first_name'],
            $order_data['last_name'],
            $order_data['email'],
            $order_data['phone'],
            $order_data['address'],
            $order_data['city'],
            $order_data['county'],
            $order_data['payment_method'],
            $order_data['payment_status'],
            $order_data['order_total']
        ]);
        
        $order_id = $conn->lastInsertId();
        
        // Insert order items
        $stmt = $conn->prepare("INSERT INTO order_items (
            order_id, product_id, product_name, price, quantity, 
            discount, image, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        
        foreach ($order_data['items'] as $product_id => $item) {
            $stmt->execute([
                $order_id,
                $product_id,
                $item['name'],
                $item['price'],
                $item['quantity'],
                $item['discount'] ?? 0,
                $item['image'] ?? ''
            ]);
        }
        
        $conn->commit();
        return $order_id;
        
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Order creation failed: " . $e->getMessage());
        throw new Exception("Could not create order. Please try again.");
    }
}
function get_delivery_methods() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM delivery_methods WHERE active = 1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log error and return empty array
        error_log("Error getting delivery methods: " . $e->getMessage());
        return [];
    }
}

function get_delivery_zones() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM delivery_zones WHERE active = 1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log error and return empty array
        error_log("Error getting delivery zones: " . $e->getMessage());
        return [];
    }
}
function add_order_item($order_id, $product_id, $quantity, $price, $discount) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO order_items (
            order_id, product_id, quantity, unit_price, discount, subtotal
        ) VALUES (
            :order_id, :product_id, :quantity, :unit_price, :discount, 
            (:quantity * (:unit_price - :discount))
        )");
        
        $result = $stmt->execute([
            ':order_id' => $order_id,
            ':product_id' => $product_id,
            ':quantity' => $quantity,
            ':unit_price' => $price,
            ':discount' => $discount
        ]);
        
        if (!$result) {
            error_log("Failed to add item: " . implode(", ", $stmt->errorInfo()));
            return false;
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Order item error: " . $e->getMessage());
        return false;
    }
}
/**
 * Calculate cart totals including subtotal, delivery fee, and total amount
 * 
 * @return array Array containing cart totals
 */
/**
 * Calculates cart totals with comprehensive error handling
 * 
 * @return array Returns associative array with subtotal, delivery_fee, total_amount, and delivery_method_id
 * @throws RuntimeException If critical database operations fail
 */
/**
 * Calculates cart totals with comprehensive error handling and transaction safety
 * 
 * @return array Returns associative array with subtotal, delivery_fee, total_amount, 
 *               delivery_method_id, and any errors
 */
function calculate_cart_totals($county = null) {
    // Initialize default return values
    $totals = [
        'subtotal' => 0.0,
        'delivery_fee' => 0.0,
        'total_amount' => 0.0,
        'delivery_method' => 'standard',
        'errors' => []
    ];

    // Calculate subtotal from cart items
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            try {
                if (!isset($item['price'], $item['quantity'])) {
                    throw new Exception("Invalid item structure for cart item $key");
                }
                
                $price = filter_var($item['price'], FILTER_VALIDATE_FLOAT, [
                    'options' => ['min_range' => 0]
                ]);
                $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1]
                ]);
                $discount = isset($item['discount']) ? 
                    filter_var($item['discount'], FILTER_VALIDATE_FLOAT, [
                        'options' => ['min_range' => 0]
                    ]) : 0.0;
                
                if ($price === false || $quantity === false || $discount === false) {
                    throw new Exception("Invalid numeric values for cart item $key");
                }
                
                $totals['subtotal'] += ($price * $quantity) - $discount;
            } catch (Exception $e) {
                $totals['errors'][] = $e->getMessage();
                continue;
            }
        }
    }

    // Calculate delivery fee based on county
    try {
        // Default delivery fee for outside Nairobi
        $deliveryFee = 500.0;
        $deliveryMethod = 'standard';
        
        // Check if county is Nairobi (case-insensitive)
        if ($county && strtolower(trim($county)) === 'nairobi') {
            $deliveryFee = 0.0;
            $deliveryMethod = 'free_nairobi';
        }
        
        $totals['delivery_fee'] = $deliveryFee;
        $totals['delivery_method'] = $deliveryMethod;
        
    } catch (Exception $e) {
        error_log("Delivery calculation error: " . $e->getMessage());
        $totals['delivery_fee'] = 500.0; // Fallback to default outside Nairobi fee
        $totals['errors'][] = 'Could not calculate delivery fees';
    }

    // Calculate final total (even if errors occurred)
    $totals['total_amount'] = $totals['subtotal'] + $totals['delivery_fee'];
    
    return $totals;
}
/**
 * Helper function to get default delivery method
 */
function get_default_delivery_method(PDO $pdo) {
    $stmt = $pdo->prepare("
        SELECT id, price 
        FROM delivery_methods 
        WHERE active = 1 
        ORDER BY price ASC, id ASC 
        LIMIT 1
    ");
    $stmt->execute();
    return $stmt->fetch();
}

/**
 * Helper function to validate existing delivery method
 */
function validate_delivery_method(PDO $pdo, int $method_id) {
    $stmt = $pdo->prepare("
        SELECT id, price 
        FROM delivery_methods 
        WHERE id = ? AND active = 1
    ");
    $stmt->execute([$method_id]);
    return $stmt->fetch();
}
function add_to_cart($product_id, $quantity = 1) {
    global $pdo; // Ensure database connection is available
    
    try {
        // Validate product exists and get current details
        $stmt = $pdo->prepare("SELECT id, name, price, sale_price, image FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            throw new Exception("Product not found or inactive");
        }
        
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Calculate price (use sale price if available)
        $price = $product['sale_price'] ?? $product['price'];
        
        // Calculate discount if any
        $discount = isset($product['sale_price']) ? ($product['price'] - $product['sale_price']) * $quantity : 0;
        
        // Add or update item in cart
        if (isset($_SESSION['cart'][$product_id])) {
            // Update existing item
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            $_SESSION['cart'][$product_id]['discount'] += $discount;
        } else {
            // Add new item
            $_SESSION['cart'][$product_id] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $price,
                'quantity' => $quantity,
                'discount' => $discount,
                'image' => $product['image'] ?? null
            ];
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Database error in add_to_cart: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Cart error: " . $e->getMessage());
        return false;
    }
}
function calculate_order_total() {
    $totals = calculate_cart_totals();
    return $totals['total_amount'];
}

// function process_mpesa_payment($phone, $amount) {
//     // Implement M-Pesa API integration here
//     // This is a placeholder - replace with actual API calls
    
//     // Example API call (replace with real implementation):
//     /*
//     $response = mpesa_stk_push($phone, $amount);
//     if ($response['ResponseCode'] === '0') {
//         return 'pending'; // Payment initiated
//     } else {
//         throw new Exception("M-Pesa payment failed: ".$response['ResponseDescription']);
//     }
//     */
    
//     // For testing purposes, return completed
//     return 'completed';
// }



function send_order_confirmation($email, $order_id) {
    // Implement email sending logic
    // This is a placeholder - replace with actual email code
    
    $subject = "Your Sunstore Industries Order #".$order_id;
    $message = "Thank you for your order! Your order number is ".$order_id;
    
    // mail($email, $subject, $message);
}
function get_db_connection() {
    global $conn;
    
    if (!$conn) {
        throw new Exception("Database connection not established");
    }
    
    return $conn;
}
/**
 * Process payment based on selected method
 * 
 * @param string $method Payment method (mpesa, cash, etc.)
 * @param float $amount Total amount to charge
 * @return string Payment status (pending, completed, failed)
 */
function process_payment($method, $amount) {
    try {
        switch ($method) {
            case 'mpesa':
                return process_mpesa_payment($amount);
                
            case 'cash_on_delivery':
                return 'pending';
                
            case 'card':
                return process_card_payment($amount);
                
            default:
                throw new Exception("Invalid payment method");
        }
    } catch (Exception $e) {
        error_log("Payment processing failed: " . $e->getMessage());
        return 'failed';
    }
}

/**
 * Process M-Pesa payment
 */
function process_mpesa_payment($amount) {
    // Validate phone number
    $phone = $_POST['phone'] ?? '';
    if (!preg_match('/^(\+?254|0)[17]\d{8}$/', $phone)) {
        throw new Exception("Invalid M-Pesa phone number format");
    }

    // Here you would integrate with your M-Pesa API
    // This is a mock implementation - replace with actual API calls
    
    /*
    $mpesa = new Mpesa(); // Your M-Pesa library
    $response = $mpesa->stkpush(
        $phone,
        $amount,
        'Order Payment',
        '123456' // Your merchant reference
    );
    
    if (!$response['success']) {
        throw new Exception("M-Pesa payment failed: " . $response['message']);
    }
    */
    
    // For testing purposes, we'll simulate success
    return 'pending'; // M-Pesa payments are typically pending until confirmed
}

/**
 * Process card payment
 */
function process_card_payment($amount) {
    // Validate card details
    $card = [
        'number' => $_POST['card_number'] ?? '',
        'expiry' => $_POST['card_expiry'] ?? '',
        'cvv' => $_POST['card_cvv'] ?? ''
    ];
    
    if (!validate_card($card)) {
        throw new Exception("Invalid card details");
    }
    
    // Here you would integrate with your payment gateway
    // This is a mock implementation
    
    /*
    $gateway = new PaymentGateway();
    $response = $gateway->charge($card, $amount);
    
    if (!$response->success) {
        throw new Exception("Card payment failed: " . $response->message);
    }
    */
    
    // For testing purposes, we'll simulate success
    return 'completed';
}

/**
 * Validate card details
 */
function validate_card($card) {
    // Basic validation - implement proper validation for production
    return strlen(str_replace(' ', '', $card['number'])) === 16 &&
           preg_match('/^\d{2}\/\d{2}$/', $card['expiry']) &&
           strlen($card['cvv']) === 3;
}
function initiate_mpesa_payment($phone, $amount) {
    // In a real implementation, this would call the Safaricom API
    // For testing, we'll simulate it
    
    // Format phone number (ensure it starts with 254)
    $formattedPhone = '254' . substr($phone, -9);
    
    // Generate a fake transaction ID
    $transaction_id = 'MPE' . time() . rand(100, 999);
    
    // Simulate API response
    return $transaction_id;
    
    /* Real implementation would look like:
    $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    $timestamp = date('YmdHis');
    $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . get_mpesa_access_token(),
        'Content-Type: application/json'
    ]);
    
    $curl_post_data = [
        'BusinessShortCode' => MPESA_SHORTCODE,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $formattedPhone,
        'PartyB' => MPESA_SHORTCODE,
        'PhoneNumber' => $formattedPhone,
        'CallBackURL' => MPESA_CALLBACK_URL,
        'AccountReference' => 'OnlineStore',
        'TransactionDesc' => 'Online Purchase'
    ];
    
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curl_post_data));
    
    $response = curl_exec($curl);
    $data = json_decode($response);
    
    if (isset($data->ResponseCode) && $data->ResponseCode == '0') {
        return $data->CheckoutRequestID;
    }
    
    return false;
    */
}