<?php
/**
 * Stabilis Configuration Usage Example
 * This file demonstrates how to use the Stabilis configuration array
 */

require_once __DIR__ . '/config/stabilis.php';

// Get the Stabilis array with all application data
$stabilis = StabilisConfig::getStabilis();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stabilis Configuration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
            border-left: 4px solid #007bff;
            padding-left: 10px;
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
            border-left: 4px solid #28a745;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .info-card {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border-top: 3px solid #007bff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .info-card strong {
            display: block;
            color: #555;
            margin-bottom: 5px;
        }
        .info-card span {
            font-size: 24px;
            color: #007bff;
            font-weight: bold;
        }
        .admin-info {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .admin-info h3 {
            margin-top: 0;
            color: #007bff;
        }
        .credentials {
            background: #fff9e6;
            padding: 10px;
            border-radius: 3px;
            font-family: monospace;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #007bff;
            color: white;
        }
        table tr:hover {
            background-color: #f5f5f5;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            background: #28a745;
            color: white;
            border-radius: 3px;
            font-size: 12px;
        }
        .empty {
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 <?php echo htmlspecialchars($stabilis['app_name']); ?> Configuration</h1>
        
        <div class="section">
            <p><strong>Version:</strong> <?php echo htmlspecialchars($stabilis['app_version']); ?></p>
            <p><strong>Tagline:</strong> <?php echo htmlspecialchars($stabilis['tagline']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($stabilis['description']); ?></p>
        </div>

        <h2>📊 Statistics</h2>
        <div class="info-grid">
            <div class="info-card">
                <strong>Total Users</strong>
                <span><?php echo $stabilis['stats']['total_users']; ?></span>
            </div>
            <div class="info-card">
                <strong>Total Products</strong>
                <span><?php echo $stabilis['stats']['total_products']; ?></span>
            </div>
            <div class="info-card">
                <strong>Total Orders</strong>
                <span><?php echo $stabilis['stats']['total_orders']; ?></span>
            </div>
            <div class="info-card">
                <strong>Total Revenue</strong>
                <span>$<?php echo number_format($stabilis['stats']['total_revenue'], 2); ?></span>
            </div>
        </div>

        <h2>👤 Admin User Created</h2>
        <div class="admin-info">
            <h3>✅ Admin Account Setup</h3>
            <p>An admin user has been successfully created in the Stabilis database.</p>
            <div class="credentials">
                <strong>Email:</strong> stabilisatyourservice@gmail.com<br>
                <strong>Password:</strong> 12341234<br>
                <strong>Role:</strong> <span class="badge">admin</span><br>
                <strong>Status:</strong> <span class="badge">Active</span>
            </div>
            <p><em>Use these credentials to log in to the Stabilis application.</em></p>
        </div>

        <h2>📁 Database Configuration</h2>
        <div class="section">
            <p><strong>Host:</strong> <?php echo htmlspecialchars($stabilis['database']['host']); ?></p>
            <p><strong>Database:</strong> <?php echo htmlspecialchars($stabilis['database']['name']); ?></p>
            <p><strong>Charset:</strong> <?php echo htmlspecialchars($stabilis['database']['charset']); ?></p>
        </div>

        <h2>🏷️ Product Categories</h2>
        <?php if (!empty($stabilis['categories'])): ?>
            <div class="section">
                <?php foreach ($stabilis['categories'] as $category): ?>
                    <span class="badge" style="margin-right: 10px; margin-bottom: 5px;"><?php echo htmlspecialchars($category); ?></span>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="section">
                <p class="empty">No categories found in the database.</p>
            </div>
        <?php endif; ?>

        <h2>🛍️ Products</h2>
        <?php if (!empty($stabilis['products'])): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Promo Price</th>
                        <th>Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stabilis['products'] as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['id']); ?></td>
                            <td><?php echo htmlspecialchars($product['nom']); ?></td>
                            <td><?php echo htmlspecialchars($product['categorie']); ?></td>
                            <td>$<?php echo number_format($product['prix'], 2); ?></td>
                            <td><?php echo $product['promo_prix'] ? '$' . number_format($product['promo_prix'], 2) : '-'; ?></td>
                            <td><?php echo $product['stock']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="section">
                <p class="empty">No products found in the database.</p>
            </div>
        <?php endif; ?>

        <h2>🎁 Packs</h2>
        <?php if (!empty($stabilis['packs'])): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Items</th>
                        <th>Active</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stabilis['packs'] as $pack): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pack['id']); ?></td>
                            <td><?php echo htmlspecialchars($pack['nom']); ?></td>
                            <td>$<?php echo number_format($pack['prix'], 2); ?></td>
                            <td><?php echo count($pack['items']); ?></td>
                            <td><?php echo $pack['active'] ? '✅' : '❌'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="section">
                <p class="empty">No packs found in the database.</p>
            </div>
        <?php endif; ?>

        <h2>🏅 Challenges (Defis)</h2>
        <?php if (!empty($stabilis['defis'])): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Objective</th>
                        <th>Reward</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stabilis['defis'] as $defi): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($defi['id']); ?></td>
                            <td><?php echo htmlspecialchars($defi['nom']); ?></td>
                            <td><?php echo htmlspecialchars($defi['type']); ?></td>
                            <td><?php echo substr(htmlspecialchars($defi['objectif']), 0, 50) . '...'; ?></td>
                            <td><?php echo htmlspecialchars($defi['recompense']); ?> pts</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="section">
                <p class="empty">No challenges found in the database.</p>
            </div>
        <?php endif; ?>

        <h2>📢 Site Events</h2>
        <?php if (!empty($stabilis['site_events'])): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Promo Code</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stabilis['site_events'] as $event): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['id']); ?></td>
                            <td><?php echo htmlspecialchars($event['titre']); ?></td>
                            <td><?php echo substr(htmlspecialchars($event['message']), 0, 50) . '...'; ?></td>
                            <td><?php echo htmlspecialchars($event['code_promo'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="section">
                <p class="empty">No site events found in the database.</p>
            </div>
        <?php endif; ?>

        <h2>💳 Promo Codes</h2>
        <?php if (!empty($stabilis['promo_codes'])): ?>
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Expires At</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stabilis['promo_codes'] as $promo): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($promo['code']); ?></td>
                            <td><?php echo $promo['discount']; ?>%</td>
                            <td><?php echo date('Y-m-d', strtotime($promo['expires_at'])); ?></td>
                            <td><?php echo $promo['active'] ? '✅ Active' : '❌ Inactive'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="section">
                <p class="empty">No active promo codes found in the database.</p>
            </div>
        <?php endif; ?>

        <h2>💡 Usage</h2>
        <div class="section">
            <p>To use the Stabilis configuration in your code:</p>
            <div style="background: white; padding: 15px; border-radius: 5px; border-left: 4px solid #6c757d; font-family: monospace; margin: 10px 0;">
<pre>&lt;?php
require_once __DIR__ . '/config/stabilis.php';

// Get the Stabilis array with all application data
$stabilis = StabilisConfig::getStabilis();

// Access specific data
echo $stabilis['app_name'];           // "Stabilis"
echo $stabilis['stats']['total_users']; // Number of users
echo $stabilis['products'];           // Array of products
echo $stabilis['defis'];              // Array of challenges
?&gt;</pre>
            </div>
        </div>
    </div>
</body>
</html>
