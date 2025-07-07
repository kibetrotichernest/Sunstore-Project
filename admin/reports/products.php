<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Default date range (last 30 days)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Validate dates
if (!strtotime($start_date) || !strtotime($end_date)) {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
}

// Get top selling products
$products_query = "
    SELECT 
        p.id,
        p.name,
        p.sku,
        COUNT(oi.id) as units_sold,
        SUM(oi.total) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.created_at BETWEEN ? AND ? + INTERVAL 1 DAY
    AND o.status != 'cancelled'
    GROUP BY p.id
    ORDER BY units_sold DESC
    LIMIT 10
";
$stmt = $conn->prepare($products_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$top_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get product categories performance
$categories_query = "
    SELECT 
        c.id,
        c.name,
        COUNT(oi.id) as units_sold,
        SUM(oi.total) as total_revenue
    FROM order_items oi
    JOIN product_categories pc ON oi.product_id = pc.product_id
    JOIN categories c ON pc.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.created_at BETWEEN ? AND ? + INTERVAL 1 DAY
    AND o.status != 'cancelled'
    GROUP BY c.id
    ORDER BY total_revenue DESC
    LIMIT 10
";
$stmt = $conn->prepare($categories_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$top_categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Product Performance Reports</h1>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-filter"></i> Filters
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Apply</button>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <a href="products.php" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Top Selling Products
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Units Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($top_products)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No data found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($top_products as $product): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                                    <td><?= htmlspecialchars($product['sku']) ?></td>
                                                    <td><?= $product['units_sold'] ?></td>
                                                    <td><?= CURRENCY . number_format($product['total_revenue'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Top Performing Categories
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Units Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($top_categories)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No data found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($top_categories as $category): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($category['name']) ?></td>
                                                    <td><?= $category['units_sold'] ?></td>
                                                    <td><?= CURRENCY . number_format($category['total_revenue'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            Product Performance Chart
                        </div>
                        <div class="card-body">
                            <canvas id="productsChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Product Performance Chart
const productsCtx = document.getElementById('productsChart').getContext('2d');
const productsChart = new Chart(productsCtx, {
    type: 'bar',
    data: {
        labels: [<?= implode(',', array_map(function($product) { return "'" . htmlspecialchars($product['name']) . "'"; }, $top_products)) ?>],
        datasets: [{
            label: 'Units Sold',
            data: [<?= implode(',', array_column($top_products, 'units_sold')) ?>],
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1,
            yAxisID: 'y'
        }, {
            label: 'Revenue',
            data: [<?= implode(',', array_column($top_products, 'total_revenue')) ?>],
            backgroundColor: 'rgba(75, 192, 192, 0.7)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1,
            yAxisID: 'y1',
            type: 'line'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label === 'Revenue') {
                            return label + ': ' + '<?= CURRENCY ?>' + context.raw.toFixed(2);
                        } else {
                            return label + ': ' + context.raw;
                        }
                    }
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Units Sold'
                },
                beginAtZero: true
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Revenue'
                },
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false,
                },
                ticks: {
                    callback: function(value) {
                        return '<?= CURRENCY ?>' + value;
                    }
                }
            }
        }
    }
});
</script>

<?php include '../includes/admin-footer.php'; ?>