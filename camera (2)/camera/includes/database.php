<?php
/**
 * Kết nối và thao tác với database
 */

// Kết nối database
function db_connect() {
    static $connection;
    
    if (!isset($connection)) {
        try {
            // Sử dụng thông tin từ config.php
            $connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            
            // Kiểm tra kết nối
            if (!$connection) {
                throw new Exception("Không thể kết nối đến MySQL: " . mysqli_connect_error());
            }
            
            mysqli_set_charset($connection, 'utf8mb4');
        } catch (Exception $e) {
            echo "Lỗi kết nối database: " . $e->getMessage();
            die();
        }
    }
    
    return $connection;
}

// Thực thi truy vấn
function db_query($query) {
    $connection = db_connect();
    $result = mysqli_query($connection, $query);
    
    if (!$result) {
        die("Lỗi truy vấn: " . mysqli_error($connection));
    }
    
    return $result;
}

// Lấy tất cả bản ghi
function db_fetch_all($query) {
    $result = db_query($query);
    $rows = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    
    return $rows;
}

// Lấy một bản ghi
function db_fetch_one($query) {
    $result = db_query($query);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

// Lấy giá trị đơn
function db_fetch_value($query) {
    $result = db_query($query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        return $row[0];
    }
    
    return null;
}

// Thêm bản ghi
function db_insert($table, $data) {
    $connection = db_connect();
    
    $columns = implode(", ", array_keys($data));
    $values = [];
    
    foreach ($data as $value) {
        if ($value === null) {
            $values[] = "NULL";
        } else {
            $values[] = "'" . mysqli_real_escape_string($connection, $value) . "'";
        }
    }
    
    $values_str = implode(", ", $values);
    
    $query = "INSERT INTO $table ($columns) VALUES ($values_str)";
    
    if (mysqli_query($connection, $query)) {
        return mysqli_insert_id($connection);
    }
    
    return false;
}

// Cập nhật bản ghi
function db_update($table, $data, $where) {
    $connection = db_connect();
    
    $set_values = [];
    
    foreach ($data as $column => $value) {
        if ($value === null) {
            $set_values[] = "$column = NULL";
        } else {
            $set_values[] = "$column = '" . mysqli_real_escape_string($connection, $value) . "'";
        }
    }
    
    $set_clause = implode(", ", $set_values);
    
    $query = "UPDATE $table SET $set_clause WHERE $where";
    
    return mysqli_query($connection, $query);
}

// Xóa bản ghi
function db_delete($table, $where) {
    $query = "DELETE FROM $table WHERE $where";
    return db_query($query);
}

// Đếm số bản ghi
function db_count($table, $where = '') {
    $query = "SELECT COUNT(*) FROM $table";
    
    if (!empty($where)) {
        $query .= " WHERE $where";
    }
    
    return db_fetch_value($query);
}

// Escape string
function db_escape($string) {
    $connection = db_connect();
    return mysqli_real_escape_string($connection, $string);
}
?>