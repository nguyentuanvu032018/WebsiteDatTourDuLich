# Bảo mật VivuTour theo tiêu chí CT275

Tài liệu này chỉ ra vị trí mã nguồn, cơ chế hoạt động và cách trình bày khi vấn đáp
cho các tiêu chuẩn bảo mật trong `Tieuchiduan.pdf`: **XSS, SQL Injection, CSRF,
IDOR và chứng thực người dùng**. Phần cuối trình bày các biện pháp bổ sung gồm
cookie, HTTP security headers, upload file và phân quyền SSE.

## 1. Bức tranh tổng thể

Một request làm thay đổi dữ liệu đi qua các lớp bảo vệ sau:

```text
Trình duyệt
    │ gửi cookie phiên + CSRF token
    ▼
public/index.php
    │ kiểm tra CSRF cho toàn bộ POST
    ▼
Controller
    │ kiểm tra đăng nhập/vai trò/quyền sở hữu + validation
    ▼
Model
    │ prepared statement + điều kiện userId
    ▼
PostgreSQL
    │
    ▼
View Plates
    └── escape dữ liệu trước khi tạo HTML
```

Đây là **phòng vệ nhiều lớp**. Một cơ chế không thay thế cơ chế khác. Ví dụ
prepared statement chống SQL Injection nhưng không chống IDOR; validation kiểm tra
nghiệp vụ nhưng không thay thế escape chống XSS.

---

## 2. Chống XSS

### XSS là gì?


XSS xảy ra khi dữ liệu không đáng tin được đưa vào HTML và trình duyệt hiểu nó là
mã lệnh. Ví dụ kẻ tấn công nhập:

```html
<script>alert(document.cookie)</script>
```

Nếu website xuất nguyên chuỗi này, thẻ `script` có thể được thực thi.

### Vị trí trong dự án

- Hàm escape dùng chung: `app/functions.php`, hàm `e()`.
- Template engine Plates: các file trong `app/views`.
- Ví dụ cụ thể:
  - `app/views/frontend/tours/detail.php`
  - `app/views/frontend/bookings/checkout.php`
  - `app/views/frontend/contacts/show.php`
  - `app/views/layouts/defaultCustomer.php`

Mẫu mã nguồn:

```php
<?= $this->e($tour['title']) ?>
```

Hàm tương đương của dự án:

```php
function e(mixed $value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}
```

### Cơ chế hoạt động

`htmlspecialchars()` chuyển các ký tự điều khiển HTML thành thực thể:

| Ký tự đầu vào | Kết quả an toàn |
|---|---|
| `<` | `&lt;` |
| `>` | `&gt;` |
| `"` | `&quot;` |
| `'` | `&#039;` |

Trình duyệt vì thế hiển thị dữ liệu như văn bản thay vì phân tích thành thẻ HTML.
`ENT_QUOTES` xử lý cả hai loại dấu nháy. `ENT_SUBSTITUTE` thay thế chuỗi UTF-8 lỗi
thay vì làm hỏng toàn bộ kết quả.

Với nội dung nhiều dòng, dự án dùng:

```php
<?= nl2br($this->e($contact['message'])) ?>
```

Thứ tự rất quan trọng: **escape trước**, sau đó mới `nl2br()`. Nếu làm ngược hoặc
không escape, HTML do người dùng nhập có thể lọt vào response.

### Cách trả lời vấn đáp

> Dự án chống XSS tại tầng output bằng Plates `$this->e()` và hàm `e()` sử dụng
> `htmlspecialchars`. Dữ liệu được escape ngay lúc đưa vào HTML, vì cùng một dữ liệu
> có thể an toàn trong CSDL nhưng nguy hiểm trong ngữ cảnh HTML. Validation không
> thay thế output escaping.

### Cách kiểm thử

Nhập vào nội dung liên hệ:

```html
<img src=x onerror=alert(1)>
```

Kết quả đúng: trang hiển thị chuỗi trên như văn bản hoặc nội dung vô hại, không xuất
hiện hộp thoại `alert`.

---

## 3. Chống SQL Injection

### SQL Injection là gì?

Lỗi xảy ra khi dữ liệu người dùng được nối trực tiếp vào chuỗi SQL. Ví dụ nguy hiểm:

```php
$sql = "SELECT * FROM Users WHERE email = '$email'";
```

Đầu vào có dấu nháy và câu SQL bổ sung có thể thay đổi cấu trúc truy vấn.

### Vị trí trong dự án

- Cấu hình PDO: `app/Models/PDOFactory.php`.
- Các model trong `app/Models`.
- Ví dụ rõ nhất:
  - `app/Models/User.php`
  - `app/Models/Booking.php`
  - `app/Models/ContactMessage.php`
  - `app/Models/Tour.php`
  - `app/Models/Payment.php`

Cấu hình:

```php
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false,
```

Truy vấn an toàn:

```php
$stmt = $this->db->prepare(
    'SELECT * FROM Bookings
     WHERE bookingId = :bookingId AND userId = :userId
     LIMIT 1'
);

$stmt->execute([
    'bookingId' => $bookingId,
    'userId' => $userId,
]);
```

### Cơ chế hoạt động

Quá trình có hai phần:

1. `prepare()` gửi cấu trúc câu SQL có placeholder tới PostgreSQL.
2. `execute()` gắn dữ liệu vào placeholder.

PostgreSQL xem giá trị được gắn là **dữ liệu**, không phân tích nó như cú pháp SQL.
`ATTR_EMULATE_PREPARES => false` yêu cầu dùng prepared statement thật của driver
PostgreSQL.

Tên bảng và tên cột không thể truyền qua placeholder. Trong `User.php`, khi cần tên
cột động, dự án kiểm tra bằng danh sách cho phép:

```php
if (!in_array($column, ['email', 'phoneNumber'], true)) {
    return false;
}
```

Chỉ sau khi qua whitelist, `$column` mới được đặt vào câu SQL. Giá trị tìm kiếm vẫn
đi qua placeholder `:value`.

### Cách trả lời vấn đáp

> Dự án sử dụng PDO prepared statement với named placeholder. Cấu trúc SQL được
> chuẩn bị riêng, dữ liệu được bind ở `execute`, vì vậy đầu vào không thể biến thành
> toán tử hoặc câu SQL mới. Với thành phần không bind được như tên cột, nhóm sử dụng
> whitelist.

### Cách kiểm thử

Thử tìm kiếm hoặc đăng nhập bằng chuỗi:

```text
' OR 1=1 --
```

Kết quả đúng: chuỗi được xử lý như một giá trị bình thường, không đăng nhập được và
không làm truy vấn trả toàn bộ bảng.

---

## 4. Chống CSRF

### CSRF là gì?

Trình duyệt tự gửi cookie session đến đúng website. Một trang độc hại có thể tạo form
POST đến VivuTour và lợi dụng cookie đang đăng nhập của nạn nhân. CSRF token giúp
server phân biệt form của VivuTour với request được tạo từ website khác.

### Vị trí trong dự án

- Sinh và kiểm tra token: `app/functions.php`
  - `csrf_token()`
  - `csrf_field()`
  - `csrf_token_is_valid()`
  - `require_valid_csrf_token()`
- Kiểm tra tập trung: `public/index.php`.
- Các form POST: `app/views`, ví dụ checkout, login, profile và trang admin.

Token được tạo:

```php
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
```

Đưa vào form:

```php
<?= csrf_field() ?>
```

HTML được sinh:

```html
<input type="hidden" name="csrf_token" value="...">
```

Kiểm tra tập trung:

```php
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_valid_csrf_token();
}
```

So sánh:

```php
hash_equals($stored, $submitted);
```

### Cơ chế hoạt động

1. Khi tạo session, server sinh 32 byte ngẫu nhiên và chuyển thành chuỗi hex.
2. Form do server render chứa token trong input ẩn.
3. Khi submit, trình duyệt gửi cả cookie session và token.
4. Server lấy token chuẩn từ session và token gửi từ POST/header.
5. `hash_equals()` so sánh hai giá trị.
6. Sai hoặc thiếu token: trả HTTP `419` và dừng request.

Kẻ tấn công có thể yêu cầu trình duyệt gửi cookie nhưng không đọc được token trong
trang VivuTour do same-origin policy, nên không tạo được POST hợp lệ.

### Tại sao kiểm tra ở `public/index.php`?

Mọi route POST đều đi qua front controller. Kiểm tra tập trung làm giảm nguy cơ lập
trình viên quên gọi kiểm tra trong một controller mới.

### Cách trả lời vấn đáp

> Token CSRF ngẫu nhiên được lưu trong session và đính kèm mọi form POST. Front
> controller kiểm tra tất cả POST bằng `hash_equals`. Cookie chứng minh người dùng
> có phiên đăng nhập, còn CSRF token chứng minh request xuất phát từ giao diện do
> ứng dụng tạo.

### Cách kiểm thử

Mở DevTools, xóa input `csrf_token` khỏi một form POST rồi submit. Kết quả đúng:

- HTTP status `419`.
- Không có dữ liệu nào được thêm, sửa hoặc xóa.

---

## 5. Chống IDOR

### IDOR là gì?

IDOR xảy ra khi server tin ID trên URL/form mà không kiểm tra tài nguyên thuộc về ai.
Ví dụ khách hàng có booking `10` sửa URL thành booking `11` và xem đơn của người khác.

### Vị trí bảo vệ booking

- Controller: `app/Controllers/Customer/BookingController.php`
  - `ownedBooking()`
  - `cancel()`
- Model: `app/Models/Booking.php`
  - `findForUser()`
  - `cancelForUser()`

Controller lấy danh tính từ session:

```php
$userId = AUTHGUARD()->user()?->userId ?? -1;
```

Sau đó truy vấn đồng thời ID tài nguyên và chủ sở hữu:

```sql
WHERE bookingId = :bookingId
  AND userId = :userId
```

Khi hủy đơn:

```sql
UPDATE Bookings
SET status = 'CANCELLED'
WHERE bookingId = :bookingId
  AND userId = :userId
  AND status IN ('PENDING', 'CONFIRMED')
```

### Vị trí bảo vệ liên hệ

- `app/Controllers/Customer/ContactController.php`
- `app/Models/ContactMessage.php`
- Hàm `findForCustomer($contactId, $customerUserId)`.

Khách hàng chỉ xem hoặc phản hồi liên hệ khi `contactId` đồng thời thuộc đúng
`customerUserId`.

### Cơ chế hoạt động

1. ID tài nguyên có thể lấy từ URL vì ID không phải bí mật.
2. ID người dùng **không lấy từ form/URL**, mà lấy từ session đã xác thực.
3. Model thêm cả hai ID vào điều kiện SQL.
4. Không khớp quyền sở hữu thì không tìm thấy bản ghi.
5. Controller trả 404 thay vì tiết lộ rằng tài nguyên của người khác có tồn tại.

Ẩn nút trên HTML không phải biện pháp bảo mật vì người dùng có thể tự sửa request.
Kiểm tra bắt buộc phải diễn ra ở server.

### Cách trả lời vấn đáp

> Dự án chống IDOR bằng kiểm tra quyền sở hữu ở cả controller và truy vấn model.
> `bookingId` lấy từ request nhưng `userId` lấy từ session. Câu SQL yêu cầu hai giá
> trị cùng khớp. Vì vậy sửa ID trên URL không cho phép truy cập đơn của người khác.

### Cách kiểm thử

1. Đăng nhập tài khoản A và ghi lại ID booking của A.
2. Đăng nhập tài khoản B.
3. Thay ID trên URL thanh toán/chi tiết bằng ID của A.
4. Kết quả đúng: nhận 404 hoặc bị từ chối, không thấy dữ liệu của A.

---

## 6. Chứng thực và lưu mật khẩu

### Vị trí trong dự án

- `app/Models/User.php`: `fill()`, `verifyPassword()`, `updatePassword()`.
- `app/SessionGuard.php`: `login()`, `user()`, `logout()`.
- `app/Controllers/Auth/LoginController.php`.
- `app/Controllers/Auth/RegisterController.php`.

Tạo hash:

```php
$this->password = password_hash(
    (string)$data['password'],
    PASSWORD_DEFAULT
);
```

Xác minh:

```php
password_verify($password, $this->password);
```

### Cơ chế hoạt động

`password_hash()` tạo hash một chiều kèm salt. CSDL không cần và không nên lưu mật
khẩu rõ. Khi đăng nhập, `password_verify()` dùng mật khẩu người dùng nhập và thông tin
thuật toán nằm trong hash để kiểm tra.

Không dùng MD5/SHA-1/SHA-256 trực tiếp vì các hàm này quá nhanh đối với mật khẩu và
không tự quản lý cost/salt phù hợp.

Sau khi đăng nhập thành công:

```php
session_regenerate_id(true);http
```

Lệnh này tạo session ID mới và xóa phiên bản cũ, chống **session fixation**: kẻ tấn
công không thể chuẩn bị trước một ID phiên rồi chờ nạn nhân đăng nhập vào ID đó.

Thông báo đăng nhập sai dùng chung:

```text
Email hoặc mật khẩu không chính xác.
```

Không nói riêng “email không tồn tại” để hạn chế dò danh sách tài khoản.

### Cách trả lời vấn đáp

> Dự án lưu mật khẩu bằng `password_hash(PASSWORD_DEFAULT)` và xác minh bằng
> `password_verify`. Sau đăng nhập, hệ thống đổi session ID để chống session fixation.
> Hệ thống không lưu mật khẩu rõ và không trả thông báo giúp phân biệt tài khoản có
> tồn tại hay không.

---

## 7. Phân quyền admin và customer

### Vị trí

- `app/Controllers/Controller.php`
  - `requireLogin()`
  - `requireCustomer()`
  - `requireAdmin()`
- Constructor của các controller trong `app/Controllers/Admin`.
- `app/SessionGuard.php`: `detectRoleByUserId()` và `isAdmin()`.

Luồng:

1. Đăng nhập thành công.
2. Server truy vấn bảng `Admin`.
3. Session chỉ lưu một trong hai vai trò cho phép: `admin` hoặc `customer`.
4. Controller admin gọi `requireAdmin()`.
5. Controller nghiệp vụ khách hàng gọi `requireCustomer()`.
6. Sai vai trò thì redirect, không chạy truy vấn/thao tác phía sau.

Điểm quan trọng: menu chỉ là giao diện. Dù người dùng tự nhập `/admin/...`, controller
vẫn kiểm tra quyền trên server.

---

## 8. Bảo vệ cookie và session

### Vị trí

`public/index.php`:

```php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
```

Ý nghĩa:

- `lifetime = 0`: cookie phiên hết hạn khi đóng trình duyệt.
- `path = /`: dùng được cho toàn bộ website.
- `Secure`: chỉ gửi qua HTTPS khi website chạy HTTPS.
- `HttpOnly`: JavaScript không đọc được cookie session.
- `SameSite=Lax`: giảm gửi cookie trong request chéo trang, hỗ trợ chống CSRF.

Khi logout, `SessionGuard`:

1. Xóa dữ liệu `$_SESSION`.
2. Làm cookie cũ hết hạn.
3. `session_destroy()`.
4. Xóa ID phiên đang giữ trong request.
5. Tạo phiên khách mới và CSRF token mới.

---

## 9. HTTP security headers

### Vị trí

`public/index.php`:

```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
```

Ý nghĩa:

- `nosniff`: không cho trình duyệt tự đoán kiểu file khác với `Content-Type`.
- `SAMEORIGIN`: chỉ cho cùng website nhúng trang vào frame, giảm clickjacking.
- `Referrer-Policy`: hạn chế thông tin URL được gửi sang nguồn khác.
- `Permissions-Policy`: tắt camera, microphone và vị trí vì ứng dụng không cần.

Các header này là phòng vệ bổ sung, không thay thế năm tiêu chí bảo mật chính.

---

## 10. Bảo mật upload file

### Vị trí

- `app/Services/ImageUploader.php`.
- `app/Controllers/Admin/AdminLocationController.php`.

Các lớp kiểm tra:

1. Chỉ admin được vào controller upload.
2. Tối đa 12 file và 5 MB mỗi file.
3. `is_uploaded_file()` xác nhận đây là file do cơ chế HTTP upload tạo.
4. `finfo` xác định MIME từ nội dung, không tin `$_FILES['type']`.
5. `getimagesize()` xác nhận cấu trúc ảnh và lấy kích thước.
6. Giới hạn 40 triệu pixel để giảm nguy cơ cạn bộ nhớ khi xử lý ảnh.
7. Chỉ cho MIME JPEG, PNG, WEBP và GIF.
8. Sinh tên bằng thời gian và `random_bytes`, không dùng tên file gốc.
9. Nếu một file lỗi, xóa các file đã lưu trong cùng lượt.

Tên file khi đọc/xóa còn được giới hạn:

```php
/^[A-Za-z0-9._-]+$/
```

Điều này ngăn chuỗi `../` dùng để thoát khỏi thư mục upload.

---

## 11. Bảo vệ SSE

### Vị trí

- Server: `app/Controllers/System/PresenceController.php`.
- Client: `public/js/admin.js`.

Trước khi mở stream:

```php
$this->requireAdmin();
```

Do đó người dùng thường không thể kết nối tới dữ liệu thống kê hiện diện. Session ID
được băm SHA-256 trước khi lưu trong `UserPresence`, tránh lưu token phiên thật vào
CSDL.

---

## 12. Bảng ghi nhớ nhanh khi vấn đáp

| Tiêu chuẩn | Cơ chế chính | File tiêu biểu |
|---|---|---|
| XSS | Escape output bằng Plates/`htmlspecialchars` | `app/functions.php`, `app/views` |
| SQL Injection | PDO prepared statement + placeholder | `app/Models/*`, `PDOFactory.php` |
| CSRF | Token session + input ẩn + kiểm tra mọi POST | `app/functions.php`, `public/index.php` |
| IDOR | ID tài nguyên + user ID lấy từ session trong SQL | `Booking.php`, `ContactMessage.php` |
| Chứng thực | `password_hash`, `password_verify`, session | `User.php`, `SessionGuard.php` |
| Phân quyền | `requireAdmin`, `requireCustomer` | `Controller.php`, các controller |
| Upload | MIME thật, kích thước, tên ngẫu nhiên | `ImageUploader.php` |
| Session | Secure/HttpOnly/SameSite, đổi session ID | `public/index.php`, `SessionGuard.php` |

## 13. Câu trả lời tổng hợp ngắn

> Website áp dụng phòng vệ nhiều lớp. XSS được xử lý tại output bằng Plates escape.
> SQL Injection được xử lý bằng PDO prepared statement và whitelist cho thành phần
> SQL động. CSRF dùng token ngẫu nhiên lưu trong session và kiểm tra tập trung mọi
> POST. IDOR được ngăn bằng cách lấy user ID từ session rồi thêm quyền sở hữu vào câu
> SQL. Mật khẩu được băm bằng `password_hash` và xác minh bằng `password_verify`;
> session ID được đổi sau đăng nhập. Ngoài ra website có phân quyền admin/customer,
> cookie HttpOnly/SameSite, security headers và kiểm tra upload dựa trên nội dung file.

