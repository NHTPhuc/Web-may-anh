<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once 'functions.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Lấy thông tin người dùng hiện tại
$user_id = $_SESSION['user_id'];
$conn = db_connect();
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$current_user = mysqli_fetch_assoc($user_result);

// Xử lý xóa người dùng
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    
    // Không cho phép xóa chính mình
    if ($delete_id == $user_id) {
        $error_message = "Bạn không thể xóa tài khoản của chính mình!";
    } else {
        // Kiểm tra người dùng có tồn tại không
        $check_user = mysqli_query($conn, "SELECT * FROM users WHERE id = $delete_id");
        
        if (mysqli_num_rows($check_user) > 0) {
            // Xóa người dùng
            if (mysqli_query($conn, "DELETE FROM users WHERE id = $delete_id")) {
                $success_message = "Đã xóa người dùng thành công!";
            } else {
                $error_message = "Lỗi khi xóa người dùng: " . mysqli_error($conn);
            }
        } else {
            $error_message = "Người dùng không tồn tại!";
        }
    }
}

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';

if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where = "WHERE username LIKE '%$search%' OR email LIKE '%$search%' OR fullname LIKE '%$search%'";
}

// Lọc theo vai trò
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';
if (!empty($role_filter)) {
    $role_filter = mysqli_real_escape_string($conn, $role_filter);
    $where = empty($where) ? "WHERE role = '$role_filter'" : $where . " AND role = '$role_filter'";
}

// Lấy tổng số người dùng
$total_query = "SELECT COUNT(*) as total FROM users $where";
$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_users = $total_row['total'];
$total_pages = ceil($total_users / $limit);

// Lấy danh sách người dùng
$users_query = "SELECT * FROM users $where ORDER BY id DESC LIMIT $offset, $limit";
$users = db_fetch_all($users_query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng - <?php echo SITE_NAME; ?> Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: #fff;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #3d5166;
        }
        
        .sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 12px;
            color: #ccc;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu ul {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 10px 20px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: #3d5166;
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .header h1 {
            font-size: 24px;
            color: #2c3e50;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info span {
            margin-right: 15px;
        }
        
        .logout-btn {
            background-color: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 3px;
            padding: 8px 15px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background-color: #c0392b;
        }
        
        .content-section {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .content-header h2 {
            font-size: 18px;
            color: #2c3e50;
        }
        
        .add-btn {
            background-color: #2ecc71;
            color: #fff;
            border: none;
            border-radius: 3px;
            padding: 8px 15px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
        }
        
        .add-btn i {
            margin-right: 5px;
        }
        
        .add-btn:hover {
            background-color: #27ae60;
        }
        
        .filter-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .search-form {
            display: flex;
            align-items: center;
        }
        
        .search-form input[type="text"] {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 3px;
            width: 250px;
            font-size: 14px;
        }
        
        .search-form button {
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 0 3px 3px 0;
            padding: 8px 15px;
            cursor: pointer;
            margin-left: -1px;
        }
        
        .filter-form {
            display: flex;
            align-items: center;
        }
        
        .filter-form select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 14px;
            margin-right: 10px;
        }
        
        .filter-form button {
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 3px;
            padding: 8px 15px;
            cursor: pointer;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th,
        table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        table th {
            background-color: #f9f9f9;
            font-weight: 600;
            color: #333;
        }
        
        table tr:hover {
            background-color: #f5f5f5;
        }
        
        .role-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .role-admin {
            background-color: #d5f5e3;
            color: #1e8449;
        }
        
        .role-user {
            background-color: #d6eaf8;
            color: #2874a6;
        }
        
        .action-btn {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            text-decoration: none;
            color: #fff;
            margin-right: 5px;
        }
        
        .btn-view {
            background-color: #3498db;
        }
        
        .btn-edit {
            background-color: #2ecc71;
        }
        
        .btn-delete {
            background-color: #e74c3c;
        }
        
        .action-btn:hover {
            opacity: 0.8;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination a,
        .pagination span {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 5px;
            border-radius: 3px;
            text-decoration: none;
            color: #333;
            background-color: #fff;
            border: 1px solid #ddd;
        }
        
        .pagination a:hover {
            background-color: #f5f5f5;
        }
        
        .pagination .current {
            background-color: #3498db;
            color: #fff;
            border-color: #3498db;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 3px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .empty-message {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><?php echo SITE_NAME; ?></h2>
                <p>Trang quản trị</p>
            </div>
            
            <div class="sidebar-menu">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Bảng điều khiển</a></li>
                    <li><a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a></li>
                    <li><a href="categories.php"><i class="fas fa-list"></i> Danh mục</a></li>
                    <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
                    <li><a href="users.php" class="active"><i class="fas fa-users"></i> Người dùng</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Cài đặt</a></li>
                    <li><a href="../"><i class="fas fa-home"></i> Xem trang web</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                </ul>
            </div>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Quản lý người dùng</h1>
                <div class="user-info">
                    <span>Xin chào, <?php echo htmlspecialchars($current_user['username']); ?></span>
                    <a href="logout.php" class="logout-btn">Đăng xuất</a>
                </div>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="content-section">
                <div class="content-header">
                    <h2>Danh sách người dùng</h2>
                    <a href="user_add.php" class="add-btn"><i class="fas fa-plus"></i> Thêm người dùng mới</a>
                </div>
                
                <div class="filter-section">
                    <form action="" method="get" class="search-form">
                        <input type="text" name="search" placeholder="Tìm kiếm người dùng..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                    
                    <form action="" method="get" class="filter-form">
                        <select name="role">
                            <option value="">Tất cả vai trò</option>
                            <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="user" <?php echo $role_filter == 'user' ? 'selected' : ''; ?>>Người dùng</option>
                        </select>
                        <button type="submit">Lọc</button>
                    </form>
                </div>
                
                <?php if (empty($users)): ?>
                    <div class="empty-message">
                        <p>Không có người dùng nào.</p>
                        <p>Hãy <a href="user_add.php">thêm người dùng mới</a> để bắt đầu.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên đăng nhập</th>
                                <th>Email</th>
                                <th>Họ tên</th>
                                <th>Vai trò</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><img src="<?php echo SITE_URL; ?>/assets/images/OIP.jpg" alt="Avatar" style="width:32px;height:32px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:8px;">
<?php echo htmlspecialchars($user['fullname'] ?? ''); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $user['role']; ?>">
                                            <?php echo $user['role'] == 'admin' ? 'Admin' : 'Người dùng'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo isset($user['created_at']) ? date('d/m/Y H:i', strtotime($user['created_at'])) : 'N/A'; ?></td>
                                    <td>
                                        <a href="user_view.php?id=<?php echo $user['id']; ?>" class="action-btn btn-view">Xem</a>
                                        <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="action-btn btn-edit">Sửa</a>
                                        <?php if ($user['id'] != $user_id): ?>
                                            <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng này?');">Xóa</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>">&laquo; Trước</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($role_filter) ? '&role=' . urlencode($role_filter) : ''; ?>">Tiếp &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
