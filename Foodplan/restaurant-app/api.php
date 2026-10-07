<?php
declare(strict_types=1);

session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();
require_once __DIR__ . '/php_helpers.php';
try {
    require_once __DIR__ . '/db.php';
} catch (PDOException $error) {
    error_log('Foodplan PHP database connection failed: ' . $error->getMessage());
    json_response(['error' => 'Unable to connect to the Foodplan database. Start MySQL and verify the database settings.'], 503);
}

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS ff_rider_locations (
            rider_id BIGINT UNSIGNED PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL UNIQUE,
            latitude DECIMAL(10,7) NOT NULL,
            longitude DECIMAL(10,7) NOT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (rider_id) REFERENCES ff_users(id) ON DELETE CASCADE,
            FOREIGN KEY (order_id) REFERENCES ff_orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS ff_rider_order_requests (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            rider_id BIGINT UNSIGNED NOT NULL,
            status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_ff_rider_order_request (order_id, rider_id),
            INDEX idx_ff_rider_requests_status (status, created_at),
            FOREIGN KEY (order_id) REFERENCES ff_orders(id) ON DELETE CASCADE,
            FOREIGN KEY (rider_id) REFERENCES ff_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );
} catch (Throwable $error) {
    error_log('Foodplan PHP schema check failed: ' . $error->getMessage());
    json_response(['error' => 'Foodplan database setup is incomplete. Import server/schema.sql and verify the foodplan database.'], 500);
}

$action = (string)($_GET['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$data = in_array($method, ['POST', 'PATCH', 'PUT'], true) ? request_data() : [];
$code = trim((string)($_GET['code'] ?? $data['code'] ?? ''));

try {
    switch ($action) {
        case 'health':
            $pdo->query('SELECT 1');
            json_response(['status' => 'ok', 'service' => 'Foodplan PHP']);

        case 'categories':
            $stmt = $pdo->query('SELECT id, slug, name, image_url FROM ff_categories ORDER BY name');
            json_response($stmt->fetchAll());

        case 'products':
            $where = ['p.available = 1'];
            $params = [];
            if (!empty($_GET['category']) && $_GET['category'] !== 'all') {
                $where[] = 'c.slug = ?';
                $params[] = trim((string)$_GET['category']);
            }
            if (!empty($_GET['search'])) {
                $where[] = '(p.name LIKE ? OR p.description LIKE ? OR p.store_name LIKE ?)';
                $search = '%' . trim((string)$_GET['search']) . '%';
                array_push($params, $search, $search, $search);
            }
            if (isset($_GET['maxPrice']) && is_numeric($_GET['maxPrice'])) {
                $where[] = 'p.price * (1 - p.discount_percent / 100) <= ?';
                $params[] = (float)$_GET['maxPrice'];
            }
            $sort = match ($_GET['sort'] ?? 'popular') {
                'price_asc' => 'p.price ASC',
                'price_desc' => 'p.price DESC',
                'rating' => 'p.rating DESC',
                'newest' => 'p.created_at DESC',
                default => 'p.rating DESC, p.created_at DESC',
            };
            $stmt = $pdo->prepare(
                'SELECT p.*, c.name AS category_name, c.slug AS category_slug
                 FROM ff_products p JOIN ff_categories c ON c.id = p.category_id
                 WHERE ' . implode(' AND ', $where) . " ORDER BY {$sort}"
            );
            $stmt->execute($params);
            json_response($stmt->fetchAll());

        case 'product':
            $stmt = $pdo->prepare(
                'SELECT p.*, c.name AS category_name, c.slug AS category_slug
                 FROM ff_products p JOIN ff_categories c ON c.id = p.category_id
                 WHERE p.id = ? AND p.available = 1'
            );
            $stmt->execute([(int)($_GET['id'] ?? 0)]);
            $product = $stmt->fetch();
            $product ? json_response($product) : json_response(['error' => 'Dish not found.'], 404);

        case 'signup':
            if ($method !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
            $name = trim((string)($data['name'] ?? ''));
            $email = strtolower(trim((string)($data['email'] ?? '')));
            $phone = trim((string)($data['phone'] ?? ''));
            $password = (string)($data['password'] ?? '');
            if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)
                || strlen($password) < 8 || strlen($password) > 200) {
                json_response(['error' => 'Enter a valid name and email, and a password with at least 8 characters.'], 422);
            }
            $stmt = $pdo->prepare("INSERT INTO ff_users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'customer')");
            try {
                $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
            } catch (PDOException $error) {
                if ($error->getCode() === '23000') json_response(['error' => 'An account with this email already exists.'], 409);
                throw $error;
            }
            $stmt = $pdo->prepare('SELECT * FROM ff_users WHERE id = ?');
            $stmt->execute([(int)$pdo->lastInsertId()]);
            session_regenerate_id(true);
            $_SESSION['user'] = safe_user($stmt->fetch());
            json_response(['user' => $_SESSION['user']], 201);

        case 'setup-admin':
            if ($method !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
            $adminName = 'Amartay Das';
            $adminEmail = 'admin@gmail.com';
            $riderName = 'Foodplan Rider';
            $riderEmail = 'rider@gmail.com';
            $defaultPassword = 'Amartay11Das';
            if ((int)$pdo->query("SELECT GET_LOCK('foodplan_first_admin_setup', 10)")->fetchColumn() !== 1) {
                json_response(['error' => 'First administrator setup is busy. Please try again.'], 503);
            }
            try {
                if ((int)$pdo->query("SELECT COUNT(*) FROM ff_users WHERE role = 'admin'")->fetchColumn() > 0) {
                    $pdo->query("SELECT RELEASE_LOCK('foodplan_first_admin_setup')");
                    json_response(['error' => 'An administrator account already exists. Sign in instead.'], 409);
                }
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('SELECT id, email, role FROM ff_users WHERE email IN (?, ?) FOR UPDATE');
                $stmt->execute([$adminEmail, $riderEmail]);
                $existing = [];
                foreach ($stmt->fetchAll() as $account) $existing[$account['email']] = $account;
                if (isset($existing[$adminEmail])) {
                    $pdo->rollBack();
                    $pdo->query("SELECT RELEASE_LOCK('foodplan_first_admin_setup')");
                    json_response(['error' => 'admin@gmail.com is already registered to a non-admin account. Change that account email before creating the default admin.'], 409);
                }
                if (isset($existing[$riderEmail]) && $existing[$riderEmail]['role'] !== 'rider') {
                    $pdo->rollBack();
                    $pdo->query("SELECT RELEASE_LOCK('foodplan_first_admin_setup')");
                    json_response(['error' => 'rider@gmail.com is already registered to a non-rider account. Change that account email before creating the default rider.'], 409);
                }
                $stmt = $pdo->prepare("INSERT INTO ff_users (full_name, email, password_hash, role, rider_status) VALUES (?, ?, ?, 'admin', 'offline')");
                try {
                    $stmt->execute([$adminName, $adminEmail, password_hash($defaultPassword, PASSWORD_DEFAULT)]);
                } catch (PDOException $error) {
                    if ($error->getCode() === '23000') {
                        $pdo->rollBack();
                        $pdo->query("SELECT RELEASE_LOCK('foodplan_first_admin_setup')");
                        json_response(['error' => 'The default admin email is already registered.'], 409);
                    }
                    throw $error;
                }
                if (isset($existing[$riderEmail])) {
                    $stmt = $pdo->prepare("UPDATE ff_users SET full_name = ?, password_hash = ?, active = 1, rider_status = 'offline' WHERE id = ? AND role = 'rider'");
                    $stmt->execute([$riderName, password_hash($defaultPassword, PASSWORD_DEFAULT), $existing[$riderEmail]['id']]);
                    $riderId = (int)$existing[$riderEmail]['id'];
                } else {
                    $stmt = $pdo->prepare("INSERT INTO ff_users (full_name, email, password_hash, role, rider_status) VALUES (?, ?, ?, 'rider', 'offline')");
                    $stmt->execute([$riderName, $riderEmail, password_hash($defaultPassword, PASSWORD_DEFAULT)]);
                    $riderId = (int)$pdo->lastInsertId();
                }
                $adminRow = $pdo->prepare("SELECT * FROM ff_users WHERE email = ? AND role = 'admin'");
                $adminRow->execute([$adminEmail]);
                $admin = safe_user($adminRow->fetch());
                $pdo->commit();
                $pdo->query("SELECT RELEASE_LOCK('foodplan_first_admin_setup')");
                session_regenerate_id(true);
                $_SESSION['user'] = $admin;
                json_response(['user' => $admin, 'rider_id' => $riderId], 201);
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $pdo->query("SELECT RELEASE_LOCK('foodplan_first_admin_setup')");
                throw $error;
            }

        case 'login':
            if ($method !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
            $email = strtolower(trim((string)($data['email'] ?? '')));
            $password = (string)($data['password'] ?? '');
            $role = (string)($data['role'] ?? 'customer');
            $stmt = $pdo->prepare('SELECT * FROM ff_users WHERE email = ? AND active = 1 LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($password, $user['password_hash'])
                || ($role !== 'customer' && $user['role'] !== $role)) {
                json_response(['error' => 'Email or password is incorrect.'], 401);
            }
            session_regenerate_id(true);
            $_SESSION['user'] = safe_user($user);
            if ($user['role'] === 'rider' && $user['rider_status'] === 'offline') {
                $pdo->prepare("UPDATE ff_users SET rider_status = 'available' WHERE id = ?")->execute([$user['id']]);
            }
            json_response(['user' => $_SESSION['user']]);

        case 'logout':
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $cookie = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
            }
            session_destroy();
            json_response(['message' => 'Signed out.']);

        case 'me':
            $user = current_user();
            $user ? json_response($user) : json_response(['error' => 'Please sign in to continue.'], 401);

        case 'coupons':
            $stmt = $pdo->query("SELECT id, code, description, discount_type, discount_value, minimum_order FROM ff_coupons WHERE active = 1 AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY id DESC");
            json_response($stmt->fetchAll());

        case 'validate-coupon':
            $couponCode = strtoupper(trim((string)($data['code'] ?? '')));
            $subtotal = max(0, (float)($data['subtotal'] ?? 0));
            $stmt = $pdo->prepare("SELECT * FROM ff_coupons WHERE code = ? AND active = 1 AND (expires_at IS NULL OR expires_at > NOW())");
            $stmt->execute([$couponCode]);
            $coupon = $stmt->fetch();
            if (!$coupon || $subtotal < (float)$coupon['minimum_order']) json_response(['error' => 'That coupon cannot be applied to this order.'], 422);
            $discount = $coupon['discount_type'] === 'percent'
                ? $subtotal * (float)$coupon['discount_value'] / 100
                : (float)$coupon['discount_value'];
            json_response(['code' => $coupon['code'], 'discount' => min($subtotal, $discount), 'description' => $coupon['description']]);

        case 'reservations':
            if ($method === 'GET') {
                require_user('admin');
                json_response($pdo->query('SELECT * FROM ff_reservations ORDER BY reservation_date DESC, created_at DESC LIMIT 200')->fetchAll());
            }
            if ($method !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
            $name = trim((string)($data['name'] ?? ''));
            $phone = trim((string)($data['phone'] ?? ''));
            $date = trim((string)($data['date'] ?? ''));
            $guests = (int)($data['guests'] ?? 0);
            if (mb_strlen($name) < 2 || $phone === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                || $date < date('Y-m-d') || $guests < 1 || $guests > 30) {
                json_response(['error' => 'Check the guest name, phone, date, and party size.'], 422);
            }
            $stmt = $pdo->prepare("INSERT INTO ff_reservations (customer_name, customer_phone, reservation_date, guests, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $date, $guests, mb_substr(trim((string)($data['notes'] ?? '')), 0, 500)]);
            json_response(['message' => 'Your table request has been sent. We will contact you to confirm.'], 201);

        case 'orders':
            $user = require_user();
            if ($method === 'POST') {
                if ($user['role'] !== 'customer') json_response(['error' => 'Only customers can place orders.'], 403);
                $items = $data['items'] ?? [];
                $name = trim((string)($data['name'] ?? $user['name']));
                $phone = trim((string)($data['phone'] ?? $user['phone']));
                $address = trim((string)($data['address'] ?? ''));
                $payment = (string)($data['payment_method'] ?? 'cod');
                if (!is_array($items) || !$items || $address === '' || $phone === '' || !in_array($payment, ['cod', 'bkash', 'nagad', 'card', 'mobile_banking'], true)) {
                    json_response(['error' => 'Provide delivery details and at least one valid cart item.'], 422);
                }
                $normalized = [];
                foreach ($items as $item) {
                    $id = (int)($item['id'] ?? 0);
                    $quantity = (int)($item['quantity'] ?? 0);
                    if ($id < 1 || $quantity < 1 || $quantity > 20) json_response(['error' => 'Cart contains an invalid dish or quantity.'], 422);
                    $normalized[$id] = ($normalized[$id] ?? 0) + $quantity;
                    if ($normalized[$id] > 20) json_response(['error' => 'A dish quantity cannot exceed 20.'], 422);
                }
                $pdo->beginTransaction();
                try {
                    $ids = array_keys($normalized);
                    $marks = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $pdo->prepare("SELECT id, name, price, discount_percent, stock FROM ff_products WHERE available = 1 AND id IN ({$marks}) FOR UPDATE");
                    $stmt->execute($ids);
                    $products = $stmt->fetchAll();
                    if (count($products) !== count($ids)) throw new RuntimeException('A dish in your cart is no longer available.');
                    $subtotal = 0.0;
                    foreach ($products as $product) {
                        $quantity = $normalized[(int)$product['id']];
                        if ($quantity > (int)$product['stock']) throw new RuntimeException($product['name'] . ' does not have enough stock.');
                        $subtotal += (float)$product['price'] * (1 - (float)$product['discount_percent'] / 100) * $quantity;
                    }
                    $couponCode = strtoupper(trim((string)($data['coupon_code'] ?? '')));
                    $discount = 0.0;
                    if ($couponCode !== '') {
                        $stmt = $pdo->prepare("SELECT * FROM ff_coupons WHERE code = ? AND active = 1 AND (expires_at IS NULL OR expires_at > NOW())");
                        $stmt->execute([$couponCode]);
                        $coupon = $stmt->fetch();
                        if (!$coupon || $subtotal < (float)$coupon['minimum_order']) throw new RuntimeException('That coupon cannot be applied to this order.');
                        $discount = $coupon['discount_type'] === 'percent'
                            ? $subtotal * (float)$coupon['discount_value'] / 100
                            : (float)$coupon['discount_value'];
                        $discount = min($subtotal, $discount);
                    }
                    $deliveryFee = 60.0;
                    $tax = round($subtotal * 0.05, 2);
                    $total = max(0, $subtotal + $deliveryFee + $tax - $discount);
                    $orderCode = 'FF-' . strtoupper(base_convert((string)time(), 10, 36)) . '-' . strtoupper(bin2hex(random_bytes(2)));
                    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $stmt = $pdo->prepare(
                        "INSERT INTO ff_orders (order_code, user_id, customer_name, customer_phone, delivery_address, delivery_instructions, subtotal, delivery_fee, tax, discount, total, coupon_code, delivery_otp, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'placed')"
                    );
                    $stmt->execute([$orderCode, $user['id'], $name, $phone, $address, trim((string)($data['instructions'] ?? '')), $subtotal, $deliveryFee, $tax, $discount, $total, $couponCode ?: null, $otp, $payment]);
                    $orderId = (int)$pdo->lastInsertId();
                    $itemInsert = $pdo->prepare('INSERT INTO ff_order_items (order_id, product_id, product_name, unit_price, quantity) VALUES (?, ?, ?, ?, ?)');
                    $stockUpdate = $pdo->prepare('UPDATE ff_products SET stock = stock - ? WHERE id = ?');
                    foreach ($products as $product) {
                        $quantity = $normalized[(int)$product['id']];
                        $unitPrice = (float)$product['price'] * (1 - (float)$product['discount_percent'] / 100);
                        $itemInsert->execute([$orderId, $product['id'], $product['name'], $unitPrice, $quantity]);
                        $stockUpdate->execute([$quantity, $product['id']]);
                    }
                    $addressInsert = $pdo->prepare("INSERT INTO ff_addresses (user_id, label, address, phone, instructions) SELECT ?, 'Home', ?, ?, ? WHERE NOT EXISTS (SELECT 1 FROM ff_addresses WHERE user_id = ? AND address = ?)");
                    $addressInsert->execute([$user['id'], $address, $phone, trim((string)($data['instructions'] ?? '')), $user['id'], $address]);
                    $pdo->prepare('INSERT INTO ff_payments (order_id, method, amount, status, transaction_ref) VALUES (?, ?, ?, ?, ?)')
                        ->execute([$orderId, $payment, $total, 'pending', 'PAY-' . $orderCode]);
                    notify_user($pdo, (int)$user['id'], 'Order placed', "Your order {$orderCode} has been received.");
                    $riders = $pdo->query("SELECT id FROM ff_users WHERE role = 'rider' AND active = 1")->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($riders as $riderId) {
                        notify_user($pdo, (int)$riderId, 'New customer order', "Order {$orderCode} has been placed and is awaiting admin preparation. You will be notified when it is ready to request.");
                    }
                    $pdo->commit();
                    json_response(['order_code' => $orderCode, 'subtotal' => $subtotal, 'delivery_fee' => $deliveryFee, 'tax' => $tax, 'discount' => $discount, 'total' => $total, 'status' => 'placed', 'payment_status' => 'pending', 'delivery_code' => $otp], 201);
                } catch (Throwable $error) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    if ($error instanceof RuntimeException) json_response(['error' => $error->getMessage()], 422);
                    throw $error;
                }
            }
            $params = [];
            $query = "SELECT o.order_code, o.customer_name, o.customer_phone, o.delivery_address, o.total, o.payment_method, o.payment_status, o.status, o.created_at, r.full_name AS rider_name, r.phone AS rider_phone FROM ff_orders o LEFT JOIN ff_users r ON r.id = o.rider_id";
            if ($user['role'] === 'customer') {
                $query .= ' WHERE o.user_id = ?';
                $params[] = $user['id'];
            } elseif ($user['role'] === 'rider') {
                $query .= ' WHERE o.rider_id = ?';
                $params[] = $user['id'];
            } elseif ($user['role'] !== 'admin') {
                json_response(['error' => 'Access denied.'], 403);
            }
            $stmt = $pdo->prepare($query . ' ORDER BY o.created_at DESC LIMIT 100');
            $stmt->execute($params);
            json_response($stmt->fetchAll());

        case 'order':
            $user = require_user();
            $stmt = $pdo->prepare(
                'SELECT o.*, r.full_name AS rider_name, r.phone AS rider_phone, l.latitude AS rider_latitude, l.longitude AS rider_longitude, l.updated_at AS rider_location_updated_at
                 FROM ff_orders o LEFT JOIN ff_users r ON r.id = o.rider_id
                 LEFT JOIN ff_rider_locations l ON l.rider_id = r.id AND l.order_id = o.id
                 WHERE o.order_code = ? AND (o.user_id = ? OR ? = "admin" OR (o.rider_id = ? AND ? = "rider"))'
            );
            $stmt->execute([$code, $user['id'], $user['role'], $user['id'], $user['role']]);
            $order = $stmt->fetch();
            if (!$order) json_response(['error' => 'Order not found.'], 404);
            $stmt = $pdo->prepare('SELECT product_name, unit_price, quantity FROM ff_order_items WHERE order_id = ?');
            $stmt->execute([$order['id']]);
            $order['items'] = $stmt->fetchAll();
            if ($user['role'] === 'rider' || ($user['role'] === 'customer' && !in_array($order['status'], ['out_for_delivery', 'picked_up', 'on_the_way', 'arrived'], true))) unset($order['delivery_otp']);
            json_response($order);

        case 'admin-confirm':
            require_user('admin');
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('SELECT id, user_id, status FROM ff_orders WHERE order_code = ? FOR UPDATE');
                $stmt->execute([$code]);
                $order = $stmt->fetch();
                if (!$order || $order['status'] !== 'placed') {
                    $pdo->rollBack();
                    json_response(['error' => $order ? 'Only newly placed orders can be confirmed.' : 'Order not found.'], $order ? 409 : 404);
                }
                $pdo->prepare("UPDATE ff_orders SET status = 'confirmed' WHERE id = ?")->execute([$order['id']]);
                notify_user($pdo, (int)$order['user_id'], 'Order confirmed', "Your order {$code} has been confirmed by Foodplan.");
                $pdo->commit();
                json_response(['message' => 'Order confirmed. The customer has been notified.', 'status' => 'confirmed']);
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $error;
            }

        case 'order-status':
            $user = require_user();
            $status = (string)($data['status'] ?? '');
            if ($user['role'] === 'admin') {
                $transitions = [
                    'confirmed' => ['processing', 'cancelled'],
                    'processing' => ['packaging', 'cancelled'],
                    'packaging' => ['ready', 'cancelled'],
                    'ready' => ['cancelled'],
                ];
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('SELECT id, user_id, status FROM ff_orders WHERE order_code = ? FOR UPDATE');
                $stmt->execute([$code]);
                $order = $stmt->fetch();
                if (!$order) {
                    $pdo->rollBack();
                    json_response(['error' => 'Order not found.'], 404);
                }
                if (!in_array($status, $transitions[$order['status']] ?? [], true)) {
                    $pdo->rollBack();
                    json_response(['error' => 'Move the order through confirmation, preparing, and packing before it is ready for a rider.'], 409);
                }
                $pdo->prepare('UPDATE ff_orders SET status = ? WHERE id = ? AND status = ?')->execute([$status, $order['id'], $order['status']]);
                $message = ['processing' => 'being prepared', 'packaging' => 'being packed', 'ready' => 'ready for pickup', 'cancelled' => 'cancelled'][$status];
                notify_user($pdo, (int)$order['user_id'], 'Order update', "Your order {$code} is {$message}.");
                if ($status === 'ready') {
                    $riders = $pdo->query("SELECT id FROM ff_users WHERE role = 'rider' AND active = 1")->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($riders as $riderId) {
                        notify_user($pdo, (int)$riderId, 'Delivery available', "Order {$code} is ready. Request pickup in your rider dashboard; admin approval is required.");
                    }
                } elseif ($status === 'cancelled') {
                    $stmt = $pdo->prepare("SELECT u.id FROM ff_rider_order_requests q JOIN ff_users u ON u.id = q.rider_id WHERE q.order_id = ? AND q.status = 'pending'");
                    $stmt->execute([$order['id']]);
                    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $riderId) {
                        notify_user($pdo, (int)$riderId, 'Delivery request closed', "Order {$code} was cancelled; your pickup request is closed.");
                    }
                    $pdo->prepare("UPDATE ff_rider_order_requests SET status = 'rejected' WHERE order_id = ? AND status = 'pending'")->execute([$order['id']]);
                }
                $pdo->commit();
                json_response(['message' => 'Order status updated.', 'status' => $status]);
                
            }
            if ($user['role'] !== 'rider') json_response(['error' => 'Access denied.'], 403);
            $previous = ['picked_up' => 'out_for_delivery', 'on_the_way' => 'picked_up', 'arrived' => 'on_the_way', 'delivered' => 'arrived'];
            $stmt = $pdo->prepare('SELECT id, user_id, status, delivery_otp FROM ff_orders WHERE order_code = ? AND rider_id = ?');
            $stmt->execute([$code, $user['id']]);
            $order = $stmt->fetch();
            if (!$order) json_response(['error' => 'Order not found or not assigned to your account.'], 404);
            if (($previous[$status] ?? null) !== $order['status']) json_response(['error' => 'This delivery has changed. Refresh the page to see its current status.'], 409);
            if ($status === 'delivered' && !hash_equals((string)$order['delivery_otp'], (string)($data['delivery_code'] ?? ''))) json_response(['error' => 'The delivery code is not correct.'], 422);
            $pdo->prepare('UPDATE ff_orders SET status = ? WHERE id = ?')->execute([$status, $order['id']]);
            $messages = ['picked_up' => 'Your order has been picked up by the rider.', 'on_the_way' => 'Your rider is on the way to your address.', 'arrived' => 'Your rider has arrived at your address.', 'delivered' => 'Your order has been delivered. Enjoy your meal!'];
            notify_user($pdo, (int)$order['user_id'], 'Delivery update', $messages[$status]);
            if ($status === 'delivered') $pdo->prepare("UPDATE ff_users SET rider_status = 'available' WHERE id = ?")->execute([$user['id']]);
            json_response(['message' => 'Order status updated.', 'status' => $status]);

        case 'available-orders':
            $user = require_user('rider');
            $stmt = $pdo->prepare(
                "SELECT o.order_code, o.total, o.payment_method, o.created_at, q.status AS request_status
                 FROM ff_orders o
                 LEFT JOIN ff_rider_order_requests q ON q.order_id = o.id AND q.rider_id = ?
                 WHERE o.status = 'ready' AND o.rider_id IS NULL
                 ORDER BY o.created_at ASC LIMIT 50"
            );
            $stmt->execute([$user['id']]);
            json_response($stmt->fetchAll());

        case 'rider-request-order':
        case 'accept-order':
            $user = require_user('rider');
            if ($method !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("SELECT rider_status FROM ff_users WHERE id = ? AND role = 'rider' AND active = 1 FOR UPDATE");
                $stmt->execute([$user['id']]);
                $rider = $stmt->fetch();
                if (!$rider || $rider['rider_status'] !== 'available') {
                    $pdo->rollBack();
                    json_response(['error' => 'You already have an active delivery or your rider account is unavailable.'], 409);
                }
                $stmt = $pdo->prepare("SELECT id FROM ff_orders WHERE rider_id = ? AND status IN ('out_for_delivery','picked_up','on_the_way','arrived') LIMIT 1");
                $stmt->execute([$user['id']]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    json_response(['error' => 'Complete your current delivery before requesting another order.'], 409);
                }
                $stmt = $pdo->prepare("SELECT id, user_id FROM ff_orders WHERE order_code = ? AND status = 'ready' AND rider_id IS NULL FOR UPDATE");
                $stmt->execute([$code]);
                $order = $stmt->fetch();
                if (!$order) {
                    $pdo->rollBack();
                    json_response(['error' => 'This ready order is no longer available. Refresh the list and try another.'], 409);
                }
                $stmt = $pdo->prepare('SELECT id, status FROM ff_rider_order_requests WHERE order_id = ? AND rider_id = ? FOR UPDATE');
                $stmt->execute([$order['id'], $user['id']]);
                $request = $stmt->fetch();
                if ($request && $request['status'] === 'pending') {
                    $pdo->rollBack();
                    json_response(['error' => 'You have already requested this delivery. Wait for the admin to review it.'], 409);
                }
                if ($request && $request['status'] === 'approved') {
                    $pdo->rollBack();
                    json_response(['error' => 'This request has already been approved.'], 409);
                }
                if ($request) {
                    $pdo->prepare("UPDATE ff_rider_order_requests SET status = 'pending' WHERE id = ?")->execute([$request['id']]);
                } else {
                    $pdo->prepare("INSERT INTO ff_rider_order_requests (order_id, rider_id) VALUES (?, ?)")->execute([$order['id'], $user['id']]);
                }
                $admins = $pdo->query("SELECT id FROM ff_users WHERE role = 'admin' AND active = 1")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($admins as $adminId) {
                    notify_user($pdo, (int)$adminId, 'Rider requested an order', "{$user['name']} requested order {$code}. Review the request in the admin dashboard.");
                }
                $pdo->commit();
                json_response(['message' => 'Pickup request sent. You will be notified after the admin reviews it.', 'status' => 'pending'], 201);
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $error;
            }

        case 'rider-location':
            $user = require_user('rider');
            $latitude = filter_var($data['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
            $longitude = filter_var($data['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) json_response(['error' => 'Provide valid GPS coordinates.'], 422);
            $stmt = $pdo->prepare("SELECT id FROM ff_orders WHERE rider_id = ? AND status IN ('out_for_delivery','picked_up','on_the_way','arrived') ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$user['id']]);
            $order = $stmt->fetch();
            if (!$order) json_response(['error' => 'Accept a delivery before sharing your location.'], 409);
            $stmt = $pdo->prepare('INSERT INTO ff_rider_locations (rider_id, order_id, latitude, longitude) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE order_id = VALUES(order_id), latitude = VALUES(latitude), longitude = VALUES(longitude)');
            $stmt->execute([$user['id'], $order['id'], $latitude, $longitude]);
            json_response(['message' => 'Location shared.']);

        case 'addresses':
            $user = require_user('customer');
            if ($method === 'GET') {
                $stmt = $pdo->prepare('SELECT id, label, address, phone, instructions FROM ff_addresses WHERE user_id = ? ORDER BY id DESC');
                $stmt->execute([$user['id']]);
                json_response($stmt->fetchAll());
            }
            $address = trim((string)($data['address'] ?? ''));
            $phone = trim((string)($data['phone'] ?? $user['phone']));
            if ($address === '' || $phone === '') json_response(['error' => 'Address and phone are required.'], 422);
            $stmt = $pdo->prepare('INSERT INTO ff_addresses (user_id, label, address, phone, instructions) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$user['id'], trim((string)($data['label'] ?? 'Home')), $address, $phone, trim((string)($data['instructions'] ?? ''))]);
            json_response(['message' => 'Address saved.'], 201);

        case 'notifications':
            $user = require_user();
            $stmt = $pdo->prepare('SELECT id, title, message, read_at, created_at FROM ff_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
            $stmt->execute([$user['id']]);
            json_response($stmt->fetchAll());

        case 'reviews':
            if ($method === 'GET') {
                $stmt = $pdo->query("SELECT r.*, u.full_name AS customer, p.name AS product FROM ff_reviews r JOIN ff_users u ON u.id = r.user_id JOIN ff_products p ON p.id = r.product_id ORDER BY r.created_at DESC LIMIT 100");
                json_response($stmt->fetchAll());
            }
            $user = require_user('customer');
            $productId = (int)($data['product_id'] ?? 0);
            $rating = (int)($data['rating'] ?? 0);
            $review = trim((string)($data['review'] ?? ''));
            if ($productId < 1 || $rating < 1 || $rating > 5 || mb_strlen($review) < 5 || mb_strlen($review) > 2000) json_response(['error' => 'Provide a product, a 1–5 star rating, and a review of at least 5 characters.'], 422);
            $stmt = $pdo->prepare('SELECT id FROM ff_orders o JOIN ff_order_items i ON i.order_id = o.id WHERE o.user_id = ? AND o.status = "delivered" AND i.product_id = ? LIMIT 1');
            $stmt->execute([$user['id'], $productId]);
            if (!$stmt->fetch()) json_response(['error' => 'You can review a dish after it has been delivered to you.'], 403);
            $stmt = $pdo->prepare('INSERT INTO ff_reviews (user_id, product_id, rating, review) VALUES (?, ?, ?, ?)');
            $stmt->execute([$user['id'], $productId, $rating, $review]);
            json_response(['message' => 'Thank you for sharing your review.'], 201);

        case 'support':
            $user = require_user();
            if ($method === 'GET') {
                $query = 'SELECT t.id, t.subject, t.message, t.status, t.priority, t.created_at, o.order_code FROM ff_support_tickets t LEFT JOIN ff_orders o ON o.id = t.order_id';
                if ($user['role'] === 'admin') {
                    $stmt = $pdo->query($query . ' ORDER BY t.created_at DESC LIMIT 200');
                } else {
                    $stmt = $pdo->prepare($query . ' WHERE t.user_id = ? ORDER BY t.created_at DESC LIMIT 50');
                    $stmt->execute([$user['id']]);
                }
                json_response($stmt->fetchAll());
            }
            $subject = trim((string)($data['subject'] ?? ''));
            $message = trim((string)($data['message'] ?? ''));
            $stmt = $pdo->prepare('SELECT id FROM ff_orders WHERE order_code = ? AND user_id = ?');
            $stmt->execute([trim((string)($data['order_code'] ?? '')), $user['id']]);
            $order = $stmt->fetch();
            if (mb_strlen($subject) < 4 || mb_strlen($message) < 10) json_response(['error' => 'Enter a subject and a message with at least 10 characters.'], 422);
            if (!empty($data['order_code']) && !$order) json_response(['error' => 'That order was not found in your account.'], 404);
            $stmt = $pdo->prepare('INSERT INTO ff_support_tickets (user_id, order_id, subject, message) VALUES (?, ?, ?, ?)');
            $stmt->execute([$user['id'], $order['id'] ?? null, $subject, $message]);
            json_response(['message' => 'Your support request has been sent.'], 201);

        case 'admin-overview':
            require_user('admin');
            $stats = $pdo->query("SELECT (SELECT COALESCE(SUM(total),0) FROM ff_orders WHERE payment_status <> 'refunded') AS sales, (SELECT COALESCE(SUM(total),0) FROM ff_orders WHERE DATE(created_at) = CURDATE()) AS today_sales, (SELECT COUNT(*) FROM ff_orders) AS orders, (SELECT COUNT(*) FROM ff_orders WHERE status IN ('placed','confirmed','processing','packaging','ready')) AS pending_orders, (SELECT COUNT(*) FROM ff_orders WHERE status = 'delivered') AS completed_orders, (SELECT COUNT(*) FROM ff_orders WHERE status = 'cancelled') AS cancelled_orders, (SELECT COUNT(*) FROM ff_users WHERE role = 'customer') AS customers, (SELECT COUNT(*) FROM ff_users WHERE role = 'rider' AND active = 1) AS riders")->fetch();
            $recent = $pdo->query('SELECT order_code, customer_name, total, payment_status, status, created_at FROM ff_orders ORDER BY created_at DESC LIMIT 8')->fetchAll();
            $categories = $pdo->query('SELECT c.name, COALESCE(SUM(i.unit_price * i.quantity), 0) AS sales FROM ff_categories c LEFT JOIN ff_products p ON p.category_id = c.id LEFT JOIN ff_order_items i ON i.product_id = p.id GROUP BY c.id ORDER BY sales DESC LIMIT 6')->fetchAll();
            json_response(['stats' => $stats, 'recent' => $recent, 'categories' => $categories]);

        case 'admin-products':
            require_user('admin');
            if ($method === 'GET') json_response($pdo->query('SELECT p.*, c.name AS category_name FROM ff_products p JOIN ff_categories c ON c.id = p.category_id ORDER BY p.created_at DESC')->fetchAll());
            if ($method === 'POST') {
                $category = (int)($data['category_id'] ?? 0);
                $name = trim((string)($data['name'] ?? ''));
                $description = trim((string)($data['description'] ?? ''));
                $price = (float)($data['price'] ?? 0);
                if ($category < 1 || mb_strlen($name) < 2 || $description === '' || $price <= 0) json_response(['error' => 'Provide a category, dish name, description, and positive price.'], 422);
                $stmt = $pdo->prepare("INSERT INTO ff_products (category_id, name, store_name, description, ingredients, image_url, price, discount_percent, stock, available) VALUES (?, ?, 'Foodplan Kitchen', ?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([$category, $name, $description, trim((string)($data['ingredients'] ?? '')), trim((string)($data['image_url'] ?? '')), $price, max(0, min(100, (float)($data['discount_percent'] ?? 0))), max(0, (int)($data['stock'] ?? 0))]);
                json_response(['id' => (int)$pdo->lastInsertId(), 'message' => 'Product added.'], 201);
            }
            json_response(['error' => 'Method not allowed.'], 405);

        case 'admin-riders':
            require_user('admin');
            $stmt = $pdo->query("SELECT u.id, u.full_name, u.email, u.phone, u.active, u.rider_status, SUM(o.status NOT IN ('delivered','cancelled')) AS active_orders, SUM(o.status = 'delivered') AS completed FROM ff_users u LEFT JOIN ff_orders o ON o.rider_id = u.id WHERE u.role = 'rider' GROUP BY u.id ORDER BY u.full_name");
            json_response($stmt->fetchAll());

        case 'admin-rider-requests':
            require_user('admin');
            if ($method === 'GET') {
                $stmt = $pdo->query(
                    "SELECT q.id, q.status, q.created_at, o.order_code, o.customer_name, o.delivery_address, o.total,
                            r.id AS rider_id, r.full_name AS rider_name, r.email AS rider_email, r.phone AS rider_phone
                     FROM ff_rider_order_requests q
                     JOIN ff_orders o ON o.id = q.order_id
                     JOIN ff_users r ON r.id = q.rider_id
                     WHERE q.status = 'pending'
                       AND o.status = 'ready'
                       AND o.rider_id IS NULL
                       AND r.active = 1
                       AND r.rider_status = 'available'
                     ORDER BY q.created_at ASC LIMIT 200"
                );
                json_response($stmt->fetchAll());
            }
            if ($method !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
            $requestId = (int)($data['request_id'] ?? 0);
            $decision = (string)($data['decision'] ?? '');
            if ($requestId < 1 || !in_array($decision, ['approve', 'reject'], true)) {
                json_response(['error' => 'Choose a valid pickup request and approval decision.'], 422);
            }
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare(
                    "SELECT q.id, q.order_id, q.rider_id, o.order_code, o.user_id, o.status AS order_status, o.rider_id AS assigned_rider,
                            r.full_name AS rider_name, r.active, r.rider_status
                     FROM ff_rider_order_requests q
                     JOIN ff_orders o ON o.id = q.order_id
                     JOIN ff_users r ON r.id = q.rider_id
                     WHERE q.id = ? AND q.status = 'pending'
                     FOR UPDATE"
                );
                $stmt->execute([$requestId]);
                $request = $stmt->fetch();
                if (!$request) {
                    $pdo->rollBack();
                    json_response(['error' => 'This pickup request is no longer pending. Refresh the dashboard.'], 409);
                }
                if ($decision === 'reject') {
                    $pdo->prepare("UPDATE ff_rider_order_requests SET status = 'rejected' WHERE id = ?")->execute([$requestId]);
                    notify_user($pdo, (int)$request['rider_id'], 'Pickup request declined', "Your request for order {$request['order_code']} was declined by the admin.");
                    $pdo->commit();
                    json_response(['message' => 'Pickup request declined.']);
                }
                if ($request['order_status'] !== 'ready' || $request['assigned_rider'] !== null
                    || !(int)$request['active'] || $request['rider_status'] !== 'available') {
                    $pdo->rollBack();
                    json_response(['error' => 'The order or rider is no longer available. Refresh and review another request.'], 409);
                }
                $stmt = $pdo->prepare("SELECT id FROM ff_orders WHERE rider_id = ? AND status IN ('out_for_delivery','picked_up','on_the_way','arrived') LIMIT 1");
                $stmt->execute([$request['rider_id']]);
                if ($stmt->fetch()) {
                    $pdo->rollBack();
                    json_response(['error' => 'This rider already has an active delivery.'], 409);
                }
                $pdo->prepare("UPDATE ff_orders SET rider_id = ?, status = 'out_for_delivery' WHERE id = ? AND status = 'ready' AND rider_id IS NULL")
                    ->execute([$request['rider_id'], $request['order_id']]);
                $pdo->prepare("UPDATE ff_users SET rider_status = 'busy' WHERE id = ?")->execute([$request['rider_id']]);
                $pdo->prepare("UPDATE ff_rider_order_requests SET status = 'approved' WHERE id = ?")->execute([$requestId]);
                $stmt = $pdo->prepare("SELECT rider_id FROM ff_rider_order_requests WHERE order_id = ? AND id <> ? AND status = 'pending'");
                $stmt->execute([$request['order_id'], $requestId]);
                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $otherRiderId) {
                    notify_user($pdo, (int)$otherRiderId, 'Pickup request closed', "Another rider was assigned order {$request['order_code']}.");
                }
                $pdo->prepare("UPDATE ff_rider_order_requests SET status = 'rejected' WHERE order_id = ? AND id <> ? AND status = 'pending'")
                    ->execute([$request['order_id'], $requestId]);
                notify_user($pdo, (int)$request['rider_id'], 'Pickup request approved', "Your request was approved. You can now deliver order {$request['order_code']}.");
                notify_user($pdo, (int)$request['user_id'], 'Rider assigned', "{$request['rider_name']} will deliver order {$request['order_code']}.");
                $pdo->commit();
                json_response(['message' => 'Rider request approved and assigned to the order.']);
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $error;
            }

        case 'admin-users':
            require_user('admin');
            json_response($pdo->query("SELECT u.id, u.full_name, u.email, u.phone, u.created_at, COUNT(o.id) AS order_count FROM ff_users u LEFT JOIN ff_orders o ON o.user_id = u.id WHERE u.role = 'customer' GROUP BY u.id ORDER BY u.created_at DESC")->fetchAll());

        case 'admin-categories':
            require_user('admin');
            if ($method === 'GET') json_response($pdo->query('SELECT id, slug, name FROM ff_categories ORDER BY name')->fetchAll());
            $name = trim((string)($data['name'] ?? ''));
            $slug = strtolower(trim((string)($data['slug'] ?? '')));
            if ($slug === '') $slug = trim((string)preg_replace('/[^a-z0-9]+/i', '-', $name), '-');
            if (mb_strlen($name) < 2 || !preg_match('/^[a-z0-9-]{2,60}$/', $slug)) json_response(['error' => 'Enter a valid category name and URL slug.'], 422);
            $stmt = $pdo->prepare('INSERT INTO ff_categories (name, slug) VALUES (?, ?)');
            $stmt->execute([$name, $slug]);
            json_response(['message' => 'Category added.'], 201);

        case 'admin-payments':
            require_user('admin');
            json_response($pdo->query('SELECT p.*, o.order_code, o.customer_name FROM ff_payments p JOIN ff_orders o ON o.id = p.order_id ORDER BY p.created_at DESC')->fetchAll());

        case 'admin-coupons':
            require_user('admin');
            if ($method === 'GET') json_response($pdo->query('SELECT * FROM ff_coupons ORDER BY created_at DESC')->fetchAll());
            $couponCode = strtoupper(trim((string)($data['code'] ?? '')));
            $type = (string)($data['discount_type'] ?? '');
            $value = (float)($data['discount_value'] ?? 0);
            if (mb_strlen($couponCode) < 3 || !in_array($type, ['percent', 'fixed'], true) || $value <= 0) json_response(['error' => 'Provide a valid coupon code and discount.'], 422);
            $stmt = $pdo->prepare('INSERT INTO ff_coupons (code, description, discount_type, discount_value, minimum_order) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$couponCode, trim((string)($data['description'] ?? '')), $type, $value, max(0, (float)($data['minimum_order'] ?? 0))]);
            json_response(['message' => 'Coupon created.'], 201);

        case 'admin-reservations':
            require_user('admin');
            if ($method === 'GET') json_response($pdo->query('SELECT * FROM ff_reservations ORDER BY reservation_date DESC, created_at DESC LIMIT 200')->fetchAll());
            $status = (string)($data['status'] ?? '');
            if (!in_array($status, ['requested', 'confirmed', 'cancelled'], true)) json_response(['error' => 'Choose a valid reservation status.'], 422);
            $stmt = $pdo->prepare('UPDATE ff_reservations SET status = ? WHERE id = ?');
            $stmt->execute([$status, (int)($_GET['id'] ?? 0)]);
            if (!$stmt->rowCount()) json_response(['error' => 'Reservation not found or unchanged.'], 404);
            json_response(['message' => 'Reservation status updated.']);

        case 'admin-tickets':
            require_user('admin');
            if ($method === 'GET') {
                $stmt = $pdo->query('SELECT t.*, u.full_name AS customer, u.email, o.order_code FROM ff_support_tickets t JOIN ff_users u ON u.id = t.user_id LEFT JOIN ff_orders o ON o.id = t.order_id ORDER BY t.created_at DESC LIMIT 200');
                json_response($stmt->fetchAll());
            }
            $status = (string)($data['status'] ?? '');
            $priority = (string)($data['priority'] ?? 'normal');
            if (!in_array($status, ['open', 'in_progress', 'resolved'], true) || !in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) json_response(['error' => 'Choose valid ticket values.'], 422);
            $stmt = $pdo->prepare('UPDATE ff_support_tickets SET status = ?, priority = ? WHERE id = ?');
            $stmt->execute([$status, $priority, (int)($_GET['id'] ?? 0)]);
            json_response(['message' => 'Support ticket updated.']);

        case 'admin-rider-create':
            require_user('admin');
            $name = trim((string)($data['name'] ?? ''));
            $email = strtolower(trim((string)($data['email'] ?? '')));
            $password = (string)($data['password'] ?? '');
            $phone = trim((string)($data['phone'] ?? ''));
            if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) json_response(['error' => 'Enter a valid name, email, and password of at least 8 characters.'], 422);
            $stmt = $pdo->prepare("INSERT INTO ff_users (full_name, email, phone, password_hash, role, rider_status) VALUES (?, ?, ?, ?, 'rider', 'available')");
            try {
                $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
            } catch (PDOException $error) {
                if ($error->getCode() === '23000') json_response(['error' => 'That email is already in use.'], 409);
                throw $error;
            }
            json_response(['message' => 'Delivery rider account created.'], 201);

        case 'admin-rider-toggle':
            require_user('admin');
            $active = filter_var($data['active'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $status = (string)($data['rider_status'] ?? '');
            if ($active === null || !in_array($status, ['available', 'busy', 'offline'], true)) json_response(['error' => 'Provide a valid rider status.'], 422);
            $stmt = $pdo->prepare("UPDATE ff_users SET active = ?, rider_status = ? WHERE id = ? AND role = 'rider'");
            $stmt->execute([$active ? 1 : 0, $status, (int)($_GET['id'] ?? 0)]);
            if (!$stmt->rowCount()) json_response(['error' => 'Rider not found or unchanged.'], 404);
            json_response(['message' => 'Rider updated.']);

        case 'admin-assign':
            require_user('admin');
            $riderId = (int)($data['rider_id'] ?? 0);
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("SELECT id, rider_status FROM ff_users WHERE id = ? AND role = 'rider' AND active = 1 FOR UPDATE");
                $stmt->execute([$riderId]);
                $rider = $stmt->fetch();
                if (!$rider || $rider['rider_status'] !== 'available') {
                    $pdo->rollBack();
                    json_response(['error' => 'Choose an active, available rider.'], 409);
                }
                $stmt = $pdo->prepare("SELECT id, user_id FROM ff_orders WHERE order_code = ? AND status = 'ready' AND rider_id IS NULL FOR UPDATE");
                $stmt->execute([$code]);
                $order = $stmt->fetch();
                if (!$order) {
                    $pdo->rollBack();
                    json_response(['error' => 'Only unassigned ready orders can be assigned.'], 409);
                }
                $pdo->prepare("UPDATE ff_orders SET rider_id = ?, status = 'out_for_delivery' WHERE id = ?")->execute([$riderId, $order['id']]);
                $pdo->prepare("UPDATE ff_users SET rider_status = 'busy' WHERE id = ?")->execute([$riderId]);
                $stmt = $pdo->prepare("SELECT rider_id FROM ff_rider_order_requests WHERE order_id = ? AND status = 'pending'");
                $stmt->execute([$order['id']]);
                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $requestingRiderId) {
                    if ((int)$requestingRiderId !== $riderId) {
                        notify_user($pdo, (int)$requestingRiderId, 'Pickup request closed', "Another rider was assigned order {$code}.");
                    }
                }
                $pdo->prepare("UPDATE ff_rider_order_requests SET status = CASE WHEN rider_id = ? THEN 'approved' ELSE 'rejected' END WHERE order_id = ? AND status = 'pending'")
                    ->execute([$riderId, $order['id']]);
                notify_user($pdo, $riderId, 'Delivery assigned', "The admin assigned order {$code} to you.");
                notify_user($pdo, (int)$order['user_id'], 'Rider assigned', "A delivery partner has been assigned to order {$code}.");
                $pdo->commit();
                json_response(['message' => 'Delivery rider assigned.']);
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $error;
            }

        case 'admin-payment-status':
            require_user('admin');
            $status = (string)($data['status'] ?? '');
            if (!in_array($status, ['pending', 'paid', 'failed', 'refunded'], true)) json_response(['error' => 'Invalid payment status.'], 422);
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('SELECT order_id FROM ff_payments WHERE id = ? FOR UPDATE');
                $stmt->execute([(int)($_GET['id'] ?? 0)]);
                $payment = $stmt->fetch();
                if (!$payment) {
                    $pdo->rollBack();
                    json_response(['error' => 'Payment not found.'], 404);
                }
                $pdo->prepare('UPDATE ff_payments SET status = ? WHERE id = ?')->execute([$status, (int)($_GET['id'] ?? 0)]);
                $pdo->prepare('UPDATE ff_orders SET payment_status = ? WHERE id = ?')->execute([$status, $payment['order_id']]);
                $pdo->commit();
                json_response(['message' => 'Payment status updated.']);
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $error;
            }

        case 'admin-product-update':
            require_user('admin');
            $id = (int)($_GET['id'] ?? 0);
            $fields = [];
            $values = [];
            foreach (['name' => 'string', 'description' => 'string', 'ingredients' => 'string', 'image_url' => 'string'] as $field => $type) {
                if (isset($data[$field]) && is_string($data[$field]) && trim($data[$field]) !== '') {
                    $fields[] = "{$field} = ?";
                    $values[] = trim($data[$field]);
                }
            }
            foreach (['category_id', 'price', 'discount_percent', 'stock', 'available'] as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "{$field} = ?";
                    $values[] = $field === 'available' ? (filter_var($data[$field], FILTER_VALIDATE_BOOLEAN) ? 1 : 0) : (float)$data[$field];
                }
            }
            if (!$fields) json_response(['error' => 'Provide product fields to update.'], 422);
            $values[] = $id;
            $stmt = $pdo->prepare('UPDATE ff_products SET ' . implode(', ', $fields) . ' WHERE id = ?');
            $stmt->execute($values);
            json_response(['message' => 'Product updated.']);

        case 'admin-product-delete':
            require_user('admin');
            $stmt = $pdo->prepare('UPDATE ff_products SET available = 0 WHERE id = ?');
            $stmt->execute([(int)($_GET['id'] ?? 0)]);
            json_response(['message' => 'Product deactivated.']);

        case 'admin-category-create':
            require_user('admin');
            $name = trim((string)($data['name'] ?? ''));
            $slug = strtolower(trim((string)($data['slug'] ?? '')));
            if ($slug === '') $slug = trim((string)preg_replace('/[^a-z0-9]+/i', '-', $name), '-');
            if (mb_strlen($name) < 2 || !preg_match('/^[a-z0-9-]{2,60}$/', $slug)) json_response(['error' => 'Enter a valid category name and URL slug.'], 422);
            $stmt = $pdo->prepare('INSERT INTO ff_categories (name, slug) VALUES (?, ?)');
            $stmt->execute([$name, $slug]);
            json_response(['message' => 'Category added.'], 201);

        default:
            json_response(['error' => 'API action not found.'], 404);
    }
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Foodplan PHP API error [' . $action . ']: ' . $error->getMessage());
    json_response(['error' => 'The request could not be completed. Check that the Foodplan database schema is imported and the API action is configured.'], 500);
}
