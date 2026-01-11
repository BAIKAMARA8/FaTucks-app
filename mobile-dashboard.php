<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FATUCKS ENTERPRISE</title>
    <link rel="stylesheet" href="assets/css/mobile-framework.css">
    <link rel="stylesheet" href="vendor/bootstrap/css/cerulean.theme.min.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#007bff">
</head>
<body>
    <!-- Top Header -->
    <header style="background: var(--primary-color); color: white; padding: 15px; position: fixed; top: 0; left: 0; right: 0; z-index: 999;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1 style="margin: 0; font-size: 18px;">🏪 FATUCKS</h1>
            <div>
                <span id="userGreeting">Welcome!</span>
                <button onclick="logout()" style="background: none; border: none; color: white; margin-left: 10px;">🚪</button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container-mobile" style="margin-top: 70px;">
        <!-- Quick Stats -->
        <div class="row-mobile">
            <div class="col-mobile-6">
                <div class="card-mobile">
                    <div class="card-body-mobile" style="text-align: center;">
                        <h3 style="color: var(--success-color); margin: 0;">📦</h3>
                        <h4 id="totalItems">0</h4>
                        <p>Total Items</p>
                    </div>
                </div>
            </div>
            <div class="col-mobile-6">
                <div class="card-mobile">
                    <div class="card-body-mobile" style="text-align: center;">
                        <h3 style="color: var(--info-color); margin: 0;">💰</h3>
                        <h4 id="totalSales">SLL 0</h4>
                        <p>Today's Sales</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card-mobile">
            <div class="card-header-mobile">
                <h3>⚡ Quick Actions</h3>
            </div>
            <div class="card-body-mobile">
                <div class="row-mobile">
                    <div class="col-mobile-6">
                        <button class="btn-primary-mobile" onclick="showPage('items')" style="width: 100%; margin-bottom: 10px;">
                            📦 Manage Items
                        </button>
                    </div>
                    <div class="col-mobile-6">
                        <button class="btn-primary-mobile" onclick="showPage('sales')" style="width: 100%; margin-bottom: 10px;">
                            💵 Record Sale
                        </button>
                    </div>
                    <div class="col-mobile-6">
                        <button class="btn-primary-mobile" onclick="showPage('customers')" style="width: 100%; margin-bottom: 10px;">
                            👥 Customers
                        </button>
                    </div>
                    <div class="col-mobile-6">
                        <button class="btn-primary-mobile" onclick="showPage('reports')" style="width: 100%; margin-bottom: 10px;">
                            📊 Reports
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="card-mobile">
            <div class="card-header-mobile">
                <h3>🕒 Recent Activity</h3>
            </div>
            <div class="card-body-mobile">
                <div id="recentActivity">
                    <div class="spinner-mobile" style="margin: 20px auto; display: block;"></div>
                </div>
            </div>
        </div>

        <!-- Payment Integration -->
        <div class="card-mobile">
            <div class="card-header-mobile">
                <h3>💳 Payment Center</h3>
            </div>
            <div class="card-body-mobile">
                <p>Accept payments from customers</p>
                <button class="btn-primary-mobile" onclick="window.location.href='payment/'" style="width: 100%;">
                    Open Payment Portal
                </button>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="mobile-nav">
        <a href="#" class="mobile-nav-item active" onclick="showPage('dashboard')">
            <span class="mobile-nav-icon">🏠</span>
            <span>Home</span>
        </a>
        <a href="#" class="mobile-nav-item" onclick="showPage('inventory')">
            <span class="mobile-nav-icon">📦</span>
            <span>Inventory</span>
        </a>
        <a href="#" class="mobile-nav-item" onclick="showPage('sales')">
            <span class="mobile-nav-icon">💰</span>
            <span>Sales</span>
        </a>
        <a href="#" class="mobile-nav-item" onclick="showPage('reports')">
            <span class="mobile-nav-icon">📊</span>
            <span>Reports</span>
        </a>
        <a href="#" class="mobile-nav-item" onclick="showPage('profile')">
            <span class="mobile-nav-icon">👤</span>
            <span>Profile</span>
        </a>
    </nav>

    <script>
    // Check authentication
    document.addEventListener('DOMContentLoaded', function() {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            window.location.href = 'login.php';
            return;
        }
        
        // Load user info
        loadUserInfo();
        loadDashboardData();
        loadRecentActivity();
    });

    function loadUserInfo() {
        const userInfo = JSON.parse(localStorage.getItem('user_info') || '{}');
        if (userInfo.name) {
            document.getElementById('userGreeting').textContent = `Hi, ${userInfo.name.split(' ')[0]}!`;
        }
    }

    function loadDashboardData() {
        fetch('api/dashboard-stats.php', {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalItems').textContent = data.stats.total_items || 0;
                document.getElementById('totalSales').textContent = `SLL ${data.stats.today_sales || 0}`;
            }
        })
        .catch(error => console.error('Error loading dashboard data:', error));
    }

    function loadRecentActivity() {
        fetch('api/recent-activity.php', {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            }
        })
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('recentActivity');
            if (data.success && data.activities.length > 0) {
                container.innerHTML = data.activities.map(activity => `
                    <div style="padding: 10px 0; border-bottom: 1px solid #eee;">
                        <strong>${activity.type}</strong><br>
                        <small>${activity.description}</small><br>
                        <small style="color: #666;">${activity.time}</small>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p style="text-align: center; color: #666;">No recent activity</p>';
            }
        })
        .catch(error => {
            document.getElementById('recentActivity').innerHTML = '<p style="text-align: center; color: #666;">Unable to load activity</p>';
        });
    }

    function showPage(page) {
        // Update active nav item
        document.querySelectorAll('.mobile-nav-item').forEach(item => {
            item.classList.remove('active');
        });
        event.target.closest('.mobile-nav-item').classList.add('active');

        // Navigate to page (implement SPA routing or redirect)
        switch(page) {
            case 'dashboard':
                // Already on dashboard
                break;
            case 'inventory':
            case 'items':
                window.location.href = 'inventory.php';
                break;
            case 'sales':
                window.location.href = 'sales.php';
                break;
            case 'customers':
                window.location.href = 'customers.php';
                break;
            case 'reports':
                window.location.href = 'reports.php';
                break;
            case 'profile':
                window.location.href = 'profile.php';
                break;
        }
    }

    function logout() {
        if (confirm('Are you sure you want to logout?')) {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user_info');
            window.location.href = 'login.php';
        }
    }

    // Add to home screen prompt
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        
        // Show install button
        const installBtn = document.createElement('button');
        installBtn.textContent = '📱 Install App';
        installBtn.className = 'btn-primary-mobile';
        installBtn.style.position = 'fixed';
        installBtn.style.top = '80px';
        installBtn.style.right = '15px';
        installBtn.style.zIndex = '1000';
        installBtn.onclick = () => {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    installBtn.remove();
                }
                deferredPrompt = null;
            });
        };
        document.body.appendChild(installBtn);
        
        setTimeout(() => installBtn.remove(), 10000); // Auto-hide after 10s
    });
    </script>
</body>
</html>