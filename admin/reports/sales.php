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

// Get sales summary
$sales_query = "
    SELECT 
        DATE(o.created_at) as order_date,
        COUNT(*) as order_count,
        SUM(o.total_amount) as total_sales
    FROM orders o
    WHERE o.created_at BETWEEN ? AND ? + INTERVAL 1 DAY
    AND o.status != 'cancelled'
    GROUP BY DATE(o.created_at)
    ORDER BY DATE(o.created_at)
";
$stmt = $conn->prepare($sales_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$sales_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total summary
$summary_query = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_sales,
        AVG(total_amount) as avg_order_value
    FROM orders
    WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY
    AND status != 'cancelled'
";
$stmt = $conn->prepare($summary_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get sales by status
$status_query = "
    SELECT 
        status,
        COUNT(*) as order_count,
        SUM(total_amount) as total_sales
    FROM orders
    WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY
    GROUP BY status
";
$stmt = $conn->prepare($status_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$status_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get sales by payment method
$payment_query = "
    SELECT 
        payment_method,
        COUNT(*) as order_count,
        SUM(total_amount) as total_sales
    FROM orders
    WHERE created_at BETWEEN ? AND ? + INTERVAL 1 DAY
    AND status != 'cancelled'
    GROUP BY payment_method
";
$stmt = $conn->prepare($payment_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$payment_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Sales Reports</h1>
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
                            <a href="sales.php" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Total Orders</h5>
                                    <h2 class="mb-0"><?= $summary['total_orders'] ? $summary['total_orders'] : 0 ?></h2>
                                </div>
                                <i class="fas fa-shopping-cart fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Total Sales</h5>
                                    <h2 class="mb-0"><?= CURRENCY . number_format($summary['total_sales'] ? $summary['total_sales'] : 0, 2) ?></h2>
                                </div>
                                <i class="fas fa-dollar-sign fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Avg. Order Value</h5>
                                    <h2 class="mb-0"><?= CURRENCY . number_format($summary['avg_order_value'] ? $summary['avg_order_value'] : 0, 2) ?></h2>
                                </div>
                                <i class="fas fa-chart-line fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            Sales Over Time
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            Sales by Status
                        </div>
                        <div class="card-body">
                            <canvas id="statusChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            Sales by Payment Method
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Payment Method</th>
                                            <th>Orders</th>
                                            <th>Total Sales</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($payment_data)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No data found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($payment_data as $payment): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                                                    <td><?= $payment['order_count'] ?></td>
                                                    <td><?= CURRENCY . number_format($payment['total_sales'], 2) ?></td>
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
                            Daily Sales Breakdown
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Orders</th>
                                            <th>Total Sales</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($sales_data)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No data found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($sales_data as $sale): ?>
                                                <tr>
                                                    <td><?= date('M j, Y', strtotime($sale['order_date'])) ?></td>
                                                    <td><?= $sale['order_count'] ?></td>
                                                    <td><?= CURRENCY . number_format($sale['total_sales'], 2) ?></td>
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
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Over Time Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: [<?= implode(',', array_map(function($sale) { return "'" . date('M j', strtotime($sale['order_date'])) . "'"; }, $sales_data)) ?>],
        datasets: [{
            label: 'Total Sales',
            data: [<?= implode(',', array_column($sales_data, 'total_sales')) ?>],
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            tension: 0.1
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
                        return 'Total: ' + '<?= CURRENCY ?>' + context.raw.toFixed(2);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '<?= CURRENCY ?>' + value;
                    }
                }
            }
        }
    }
});

// Sales by Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: [<?= implode(',', array_map(function($status) { return "'" . ucfirst($status['status']) . "'"; }, $status_data)) ?>],
        datasets: [{
            data: [<?= implode(',', array_column($status_data, 'total_sales')) ?>],
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = Math.round((value / total) * 100);
                        return `${label}: ${'<?= CURRENCY ?>'}${value.toFixed(2)} (${percentage}%)`;
                    }
                }
            }
        }
    }
});
</script>

<?php include '../includes/admin-footer.php'; ?>