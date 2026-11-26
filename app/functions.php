<?php

declare(strict_types=1);

/** Return the shared PDO connection created in bootstrap.php. */
if (!function_exists('PDO')) {
    function PDO(): \PDO
    {
        global $PDO;
        return $PDO;
    }
}

/** Return the shared authentication guard. */
if (!function_exists('AUTHGUARD')) {
    function AUTHGUARD(): App\SessionGuard
    {
        global $AUTHGUARD;
        return $AUTHGUARD;
    }
}

if (!function_exists('dd')) {
    function dd(mixed $var): never
    {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
        exit;
    }
}

/** Redirect and optionally place one-time data in the session. */
if (!function_exists('redirect')) {
    function redirect(string $location, array $data = []): never
    {
        foreach ($data as $key => $value) {
            $_SESSION[$key] = $value;
        }

        header('Location: ' . $location, true, 303);
        exit;
    }
}

/** Read a flash value once. */
if (!function_exists('session_get_once')) {
    function session_get_once(string $name, mixed $default = null): mixed
    {
        if (!array_key_exists($name, $_SESSION)) {
            return $default;
        }

        $value = $_SESSION[$name];
        unset($_SESSION[$name]);
        return $value;
    }
}

/** Get or create the CSRF token for the current session. */
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

/** Generate a hidden CSRF input suitable for a POST form. */
if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="'
            . htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '">';
    }
}

/** Validate a submitted CSRF token using a constant-time comparison. */
if (!function_exists('csrf_token_is_valid')) {
    function csrf_token_is_valid(?string $submitted): bool
    {
        $stored = $_SESSION['csrf_token'] ?? null;
        return is_string($stored)
            && is_string($submitted)
            && $submitted !== ''
            && hash_equals($stored, $submitted);
    }
}

/** Stop a request with a safe 419 response when its CSRF token is invalid. */
if (!function_exists('require_valid_csrf_token')) {
    function require_valid_csrf_token(): void
    {
        $submitted = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (csrf_token_is_valid(is_string($submitted) ? $submitted : null)) {
            return;
        }

        http_response_code(419);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="vi"><meta charset="utf-8"><title>Phiên làm việc không hợp lệ</title>';
        echo '<body style="font-family:system-ui;padding:2rem"><h1>Phiên làm việc đã hết hạn</h1>';
        echo '<p>Vui lòng quay lại trang trước, tải lại trang rồi thực hiện lại thao tác.</p>';
        echo '<p><a href="/">Về trang chủ</a></p></body></html>';
        exit;
    }
}

/** Escape a value when a view does not use Plates' $this->e(). */
if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/** Convert common PostgreSQL lowercase column names to the project's camelCase names. */
if (!function_exists('normalize_db_row')) {
    function normalize_db_row(array $row): array
    {
        $map = [
            'userid' => 'userId', 'phonenumber' => 'phoneNumber', 'createdat' => 'createdAt',
            'createdby' => 'createdBy', 'staffcode' => 'staffCode', 'locationid' => 'locationId',
            'locationname' => 'locationName', 'imageid' => 'imageId', 'imageurl' => 'imageUrl',
            'categoryid' => 'categoryId', 'baseprice' => 'basePrice', 'numberdays' => 'numberDays',
            'tourid' => 'tourId', 'scheduleid' => 'scheduleId', 'startdate' => 'startDate',
            'enddate' => 'endDate', 'maxparticipants' => 'maxParticipants', 'couponcode' => 'couponCode',
            'discountvalue' => 'discountValue', 'expirydate' => 'expiryDate', 'minordervalue' => 'minOrderValue',
            'createdbyadmin' => 'createdByAdmin', 'bookingid' => 'bookingId', 'totalprice' => 'totalPrice',
            'detailid' => 'detailId', 'fullname' => 'fullName', 'relationship' => 'relationship',
            'paymentid' => 'paymentId', 'paymentmethod' => 'paymentMethod', 'transactionid' => 'transactionId',
            'reviewid' => 'reviewId', 'customeruserid' => 'customerUserId', 'contactid' => 'contactId',
            'contactmethod' => 'contactMethod', 'status' => 'status',
            'tourtitle' => 'tourTitle', 'categoryname' => 'categoryName', 'customername' => 'customerName',
            'customeremail' => 'customerEmail', 'customerphone' => 'customerPhone',
            'paymentstatus' => 'paymentStatus', 'monthly_total' => 'monthlyTotal',
            'totalbooked' => 'totalBooked', 'averagerating' => 'averageRating',
            'reviewcount' => 'reviewCount', 'locationnames' => 'locationNames',
            'availableslots' => 'availableSlots', 'tourcount' => 'tourCount',
            'firstimage' => 'firstImage', 'imagecount' => 'imageCount',
            'bookingcount' => 'bookingCount', 'totalspent' => 'totalSpent',
            'bookingstatus' => 'bookingStatus', 'bookingcreatedat' => 'bookingCreatedAt',
            'schedulecount' => 'scheduleCount', 'passengercount' => 'passengerCount',
            'address' => 'address', 'message' => 'message', 'resolvedat' => 'resolvedAt',
            'replyid' => 'replyId', 'senderuserid' => 'senderUserId',
            'senderrole' => 'senderRole', 'sendername' => 'senderName', 'isread' => 'isRead',
            'unreadcount' => 'unreadCount', 'lastreplyat' => 'lastReplyAt',
            'sessionkey' => 'sessionKey', 'currentpath' => 'currentPath',
            'lastseenat' => 'lastSeenAt', 'displayname' => 'displayName',
            'secondsago' => 'secondsAgo',
            'departurepoint' => 'departurePoint', 'transport' => 'transport',
            'accommodation' => 'accommodation', 'meals' => 'meals',
            'suitablefor' => 'suitableFor', 'highlights' => 'highlights',
            'includedservices' => 'includedServices', 'excludedservices' => 'excludedServices',
            'notes' => 'notes', 'cancellationpolicy' => 'cancellationPolicy',
            'updatedat' => 'updatedAt', 'itineraryid' => 'itineraryId',
            'daynumber' => 'dayNumber',
        ];

        $normalized = [];
        foreach ($row as $key => $value) {
            $lower = strtolower((string)$key);
            $normalized[$map[$lower] ?? $key] = $value;
        }
        return $normalized;
    }
}

if (!function_exists('normalize_db_rows')) {
    function normalize_db_rows(array $rows): array
    {
        return array_map('normalize_db_row', $rows);
    }
}

/** Display booking states in Vietnamese while keeping stable English values in the database. */
if (!function_exists('booking_status_label')) {
    function booking_status_label(?string $status): string
    {
        $status = strtoupper(trim((string)$status));
        return [
            'PENDING' => 'Chờ xác nhận',
            'CONFIRMED' => 'Đã xác nhận',
            'COMPLETED' => 'Đã hoàn thành',
            'CANCELLED' => 'Đã hủy',
        ][$status] ?? 'Chờ xác nhận';
    }
}

/** Display payment states consistently in Vietnamese. */
if (!function_exists('payment_status_label')) {
    function payment_status_label(?string $status): string
    {
        $status = strtoupper(trim((string)$status));
        return [
            'UNPAID' => 'Chờ thanh toán',
            'PENDING' => 'Chờ thanh toán',
            'SUCCESS' => 'Thành công',
            'COMPLETED' => 'Thành công',
            'FAILED' => 'Không thành công',
            'CANCELLED' => 'Không thành công',
            'REFUNDED' => 'Đã hoàn tiền',
        ][$status] ?? 'Chờ thanh toán';
    }
}

/** Display contact request states consistently in Vietnamese. */
if (!function_exists('contact_status_label')) {
    function contact_status_label(?string $status): string
    {
        $status = strtoupper(trim((string)$status));
        return [
            'NEW' => 'Đang xử lý',
            'OPEN' => 'Đang xử lý',
            'RESOLVED' => 'Đã xử lý',
            'CLOSED' => 'Đã đóng',
        ][$status] ?? 'Đang xử lý';
    }
}
