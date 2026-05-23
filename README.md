# NEXUS STORE — Website Bán Tài Khoản

Trang web thương mại điện tử bán tài khoản trả phí (Game, Netflix, YouTube, Spotify, ChatGPT, Cloud, v.v.).  
Xây dựng bằng **PHP thuần**, giao diện **Bootstrap 5**, chạy trên **XAMPP** (Apache + MySQL).

---

## Yêu cầu hệ thống

| Thành phần   | Phiên bản tối thiểu |
|-------------|---------------------|
| XAMPP       | 7.4+ (khuyến nghị 8.0+) |
| PHP         | 7.4+                |
| MySQL       | 5.7+ (khuyến nghị 8.0)  |
| Apache      | Đi kèm XAMPP         |

---

## Hướng dẫn cài đặt

### 1. Tải và giải nén source

Clone repo hoặc giải nén toàn bộ source vào thư mục `htdocs` của XAMPP:

```
C:\xampp\htdocs\          (Windows)
/opt/lampp/htdocs/        (Linux)
/Applications/XAMPP/htdocs/ (macOS)
```

Sau khi giải nén, cấu trúc thư mục sẽ như sau:

```
htdocs/
├── admin/            # Admin panel (quản lý đơn hàng, sản phẩm, users, nạp tiền)
│   └── lib/          # Admin modules (layout, sidebar, verifier, ...)
├── api/              # API endpoints (giỏ hàng, thanh toán, migration)
├── assets/           # CSS, JS, ảnh
│   ├── css/
│   │   ├── admin.css
│   │   ├── nexus.css
│   │   └── product-cards.css
│   └── js/
│       ├── cart.js
│       └── nexus-ui.js
├── auth/             # Đăng nhập / Đăng ký / Đăng xuất
├── cart/             # Giỏ hàng & thanh toán thành công
├── config/           # Cấu hình database
│   └── database_config.php
├── crud/             # CRUD operations theo bảng
│   ├── users/
│   ├── products/
│   ├── categories/
│   ├── types/
│   ├── orders/
│   ├── transactions/
│   ├── cart/
│   ├── account_stock/
│   ├── deposit_requests/
│   ├── banks/
│   ├── settings/
│   └── account_field_types/
├── database/         # Kết nối MySQL
│   └── connect.php
├── lib/              # Thư viện dùng chung (cart, order, user, products, security, UI, settings)
├── migrations/       # Script tạo database schema & seed dữ liệu
│   └── setup.php
├── products/         # Trang danh sách sản phẩm & checkout
├── public/           # Tài nguyên public
│   └── admin/
│       ├── css/admin.css
│       └── js/admin.js
├── user/             # Trang người dùng (đơn hàng, nạp tiền)
├── .htaccess         # Bảo mật cơ bản
└── index.php         # Trang chủ
```

### 2. Khởi động XAMPP

Mở **XAMPP Control Panel** và **Start** 2 service:

- **Apache** (cổng 80 hoặc 443)
- **MySQL** (cổng 3306)

Nếu Apache không start được do xung đột cổng, vào Config → Apache (httpd.conf) đổi `Listen 80` thành `Listen 8080`.

### 3. Cấu hình database

Mở file `config/database_config.php` và sửa thông tin kết nối MySQL của bạn:

```php
<?php
$host = "127.0.0.1";        // MySQL host
$username = "root";         // MySQL username
$password = "";             // MySQL password
$dbname = "mydb";           // Tên database (sẽ tự tạo nếu chưa có)
?>
```

> **Lưu ý:** Mặc định XAMPP dùng user `root` không password. Nếu bạn đã đặt password cho root thì sửa lại dòng `$password`.

### 4. Tạo database & seed dữ liệu

Mở trình duyệt, truy cập:

```
http://localhost/config/setup.php
```

Script này sẽ tự động:

- Tạo database nếu chưa tồn tại
- Tạo tất cả các bảng (users, products, orders, cart, transactions, categories, types, banks, settings, account_stock, account_field_types, deposit_requests)
- Seed dữ liệu mẫu:
  - **Tài khoản admin** (xem bên dưới)
  - **10 danh mục** (Game, Netflix, YouTube, Spotify, Disney+, GPT/AI, AI Tools, Cloud, Social, Khác)
  - **34 loại sản phẩm** (Valorant, CS2, Minecraft, Genshin, LQMB, Free Fire, ChatGPT, Canva, Google One, v.v.)
  - **40+ sản phẩm mẫu** có ảnh, giá, mô tả
  - **Dữ liệu kho** (stock) mẫu cho từng sản phẩm
  - **4 ngân hàng mẫu** (MB Bank, TPBank, Vietcombank, ACB)
  - **Cài đặt mặc định**

Nếu thấy toàn bộ dòng `[OK]` màu xanh là thành công. Dòng `[SKIP]` có nghĩa dữ liệu đã tồn tại (chạy lại lần 2 sẽ không bị trùng).

### 5. Truy cập website

Sau khi setup xong:

| Trang            | URL                          |
|------------------|------------------------------|
| **Trang chủ**   | http://localhost/             |
| **Đăng nhập**   | http://localhost/auth/login.php |
| **Đăng ký**     | http://localhost/auth/register.php |
| **Admin Panel** | http://localhost/admin/       |

---

## Tài khoản mặc định

| Vai trò | Username | Password  | Số dư      |
|---------|----------|-----------|------------|
| Admin   | `admin`  | `admin123`| 1.000.000đ |

> **Khuyến nghị:** Đổi mật khẩu admin ngay sau khi đăng nhập lần đầu (vào Admin → Settings hoặc đổi trực tiếp trong database).

---


## Hướng dẫn sử dụng nhanh

### 1. Luồng sử dụng cho khách hàng

1. Mở website: `http://localhost/`
2. Xem sản phẩm ở trang chủ. Có thể lọc theo danh mục, tìm kiếm, sắp xếp theo giá/tên/mới nhất.
3. Bấm tên sản phẩm hoặc `Xem chi tiết` để mở trang chi tiết.
4. Đăng ký tài khoản tại `http://localhost/auth/register.php`.
5. Đăng nhập tại `http://localhost/auth/login.php`.
6. Nạp tiền tại `http://localhost/user/deposit.php`.
7. Thêm sản phẩm vào giỏ hoặc bấm mua ngay.
8. Vào giỏ hàng tại `http://localhost/cart/index.php`.
9. Thanh toán bằng số dư tài khoản.
10. Xem đơn hàng tại `http://localhost/user/orders.php`.
11. Xem chi tiết đơn hàng tại `http://localhost/user/order-detail.php?id=ID_DON_HANG`.

### 2. Luồng sử dụng cho Admin

Đăng nhập admin:

```
http://localhost/admin/login.php
Username: admin
Password: admin123
```

Các trang quản trị chính:

| Chức năng | URL |
|----------|-----|
| Dashboard | `http://localhost/admin/dashboard.php` |
| Sản phẩm | `http://localhost/admin/crud/products/list.php` |
| Kho tài khoản | `http://localhost/admin/crud/account_stock/list.php` |
| Field tài khoản | `http://localhost/admin/crud/account_field_types/list.php` |
| Người dùng | `http://localhost/admin/crud/users/list.php` |
| Danh mục | `http://localhost/admin/crud/categories/list.php` |
| Loại sản phẩm | `http://localhost/admin/crud/types/list.php` |
| Đơn hàng | `http://localhost/admin/crud/orders/list.php` |
| Yêu cầu nạp tiền | `http://localhost/admin/crud/deposit_requests/list.php` |
| Cấu hình SePay | `http://localhost/admin/crud/sepay_config/list.php` |
| Giao dịch SePay | `http://localhost/admin/crud/sepay_transactions/list.php` |
| Cấu hình hệ thống | `http://localhost/admin/crud/settings/list.php` |
| Đăng xuất | `http://localhost/admin/logout.php` |

### 3. Cách thêm sản phẩm để bán được

Làm theo thứ tự này để sản phẩm hiển thị và có thể bán thật:

1. Vào Admin → Danh mục.
2. Tạo danh mục nếu chưa có, ví dụ `Game`, `Netflix`, `GPT`.
3. Vào Admin → Loại sản phẩm.
4. Tạo loại sản phẩm thuộc danh mục đó, ví dụ `ChatGPT Plus`, `Netflix Premium`.
5. Vào Admin → Sản phẩm.
6. Bấm `Thêm sản phẩm`.
7. Nhập tên, danh mục, loại, giá, mô tả, ảnh, badge nếu cần.
8. Lưu sản phẩm.
9. Vào Admin → Kho tài khoản.
10. Thêm account stock cho sản phẩm đó.
11. Ra trang chủ kiểm tra sản phẩm đã hiển thị.
12. Test mua thử bằng user thường.

Lưu ý: Nếu sản phẩm chưa có stock khả dụng, khách có thể thấy hết hàng hoặc không mua được.

### 4. Cách quản lý kho tài khoản

Vào:

```
http://localhost/admin/crud/account_stock/list.php
```

Mỗi dòng trong kho là một tài khoản có thể giao cho khách sau khi mua. Tùy loại sản phẩm, dữ liệu có thể gồm:

- account/email/username
- password
- cookie
- 2FA
- recovery code
- note

Trạng thái thường dùng:

- `available`: còn hàng, có thể bán.
- `sold`: đã bán.
- `reserved`: đang giữ/chờ xử lý.

### 5. Cách xử lý nạp tiền

User nạp tiền tại:

```
http://localhost/user/deposit.php
```

Admin xử lý tại:

```
http://localhost/admin/crud/deposit_requests/list.php
```

Quy trình:

1. User tạo yêu cầu nạp tiền.
2. User chuyển khoản theo thông tin ngân hàng hoặc VietQR.
3. Admin kiểm tra giao dịch.
4. Nếu đúng, admin duyệt yêu cầu.
5. Hệ thống cộng số dư cho user.
6. Nếu sai thông tin, admin từ chối và ghi chú lý do.

Nếu đã cấu hình SePay webhook, hệ thống có thể tự động ghi nhận giao dịch phù hợp.

### 6. Quy trình test nhanh sau khi sửa code

Sau khi sửa code, nên kiểm tra các URL sau:

```
http://localhost/
http://localhost/chitiet.php?id=36
http://localhost/auth/login.php
http://localhost/cart/index.php
http://localhost/admin/login.php
http://localhost/admin/dashboard.php
http://localhost/admin/crud/products/list.php
http://localhost/admin/crud/users/list.php
http://localhost/admin/crud/categories/list.php
http://localhost/admin/crud/orders/list.php
http://localhost/admin/crud/deposit_requests/list.php
http://localhost/admin/crud/settings/list.php
```

Kiểm tra syntax PHP bằng Git Bash trong thư mục source:

```
find . -name '*.php' -not -path './_backup*' -print0 | xargs -0 -n1 /g/xampp/php/php.exe -l
```

Nếu dùng Command Prompt/PowerShell, kiểm tra từng file bằng:

```
C:\xampp\php\php.exe -l ten_file.php
```

---

## Các tính năng chính

### Phía người dùng (Frontend)

- Duyệt sản phẩm theo danh mục và loại (Game, Netflix, ChatGPT, Cloud...)
- Tìm kiếm sản phẩm
- Giỏ hàng (lưu theo session + user nếu đã đăng nhập)
- Đăng ký / Đăng nhập / Đăng xuất
- Xem lịch sử đơn hàng
- Nạp tiền qua chuyển khoản ngân hàng (upload ảnh xác nhận)
- Thanh toán bằng số dư tài khoản
- Nhận tài khoản đã mua ngay sau khi thanh toán

### Phía Admin (Backend)

- **Dashboard** tổng quan (số đơn, doanh thu, users)
- **Quản lý sản phẩm** — thêm/sửa/xóa sản phẩm, quản lý kho account
- **Quản lý danh mục & loại** sản phẩm
- **Quản lý đơn hàng** — duyệt đơn, đổi trạng thái (pending → processing → completed → cancelled → refunded)
- **Quản lý người dùng** — xem danh sách, khóa/mở, chỉnh sửa
- **Quản lý nạp tiền** — duyệt/từ chối yêu cầu nạp tiền
- **Quản lý cấu hình** — tên cửa hàng, icon, email, phí giao dịch
- **Quản lý trường dữ liệu tài khoản** (account field types) — tùy chỉnh các trường như account, password, email, cookie, 2FA...
- **Quản lý ngân hàng** nhận nạp tiền

---

## Cấu trúc database

| Bảng                  | Mô tả                          |
|-----------------------|--------------------------------|
| `users`               | Người dùng (user + admin)      |
| `categories`          | Danh mục sản phẩm              |
| `types`               | Loại sản phẩm (thuộc danh mục) |
| `products`            | Sản phẩm                       |
| `account_stock`       | Kho tài khoản (account để bán) |
| `orders`              | Đơn hàng                       |
| `cart`                | Giỏ hàng                       |
| `transactions`        | Lịch sử giao dịch số dư        |
| `banks`               | Ngân hàng nhận nạp tiền        |
| `deposit_requests`    | Yêu cầu nạp tiền               |
| `settings`            | Cấu hình cửa hàng              |
| `account_field_types` | Định nghĩa các trường account  |

---

## Công nghệ sử dụng

- **PHP** thuần (không framework)
- **MySQL** với charset `utf8mb4` (hỗ trợ tiếng Việt + emoji)
- **Bootstrap 5.3** — giao diện responsive
- **Font Awesome 6.4** — icon
- **Google Fonts** — Plus Jakarta Sans
- **Unsplash** — ảnh sản phẩm mẫu
- **bcrypt** (`password_hash`) — mã hóa mật khẩu

---

## Lưu ý bảo mật

- **KHÔNG** commit file `config/database_config.php` chứa password thật lên GitHub
- File `.htaccess` đã chặn truy cập trực tiếp vào file ẩn (bắt đầu bằng dấu `.`)
- Nên đặt password mạnh cho user `root` MySQL
- Khuyến nghị bật HTTPS khi deploy production
- Đổi `session_regenerate_id(true)` đã có sẵn khi login để chống session fixation

---

## Xử lý lỗi thường gặp

| Lỗi                              | Cách sửa                                                                      |
|----------------------------------|-------------------------------------------------------------------------------|
| **"Ket noi MySQL that bai"**     | Kiểm tra XAMPP đã start MySQL chưa. Sửa lại thông tin trong `config/database_config.php` |
| **Trang trắng / 500 Error**      | Bật `display_errors = On` trong `php.ini`. Kiểm tra file `database/connect.php` |
| **Font/Icon không load**         | Máy cần có internet để load Bootstrap, Font Awesome, Google Fonts từ CDN      |
| **"Undefined variable"**         | Đảm bảo PHP version >= 7.4                                                    |
| **Apache không start**           | Đổi cổng Apache (httpd.conf) hoặc tắt Skype/VMware đang chiếm cổng 80/443     |

---

## Triển khai production

1. Upload toàn bộ source lên hosting (trừ file `.git/` và `.gitignore`)
2. Sửa `config/database_config.php` theo thông tin MySQL trên hosting
3. Chạy `https://yourdomain.com/config/setup.php` một lần duy nhất
4. **XÓA** hoặc **đổi tên** file `config/setup.php` sau khi setup xong để tránh bị chạy lại
5. Đổi mật khẩu admin mặc định
6. Bật HTTPS (Let's Encrypt hoặc SSL hosting)

---

## License

Dự án dành cho mục đích học tập và cá nhân.


## Cập nhật chức năng mua nhiều tài khoản

- Ở trang chi tiết sản phẩm, khách có thể tăng/giảm `Số lượng` trước khi bấm `Mua ngay`.
- Hệ thống sẽ lấy đúng số lượng tài khoản khả dụng trong `account_stock`.
- Tổng tiền = giá 1 tài khoản × số lượng.
- Nếu kho không đủ số lượng yêu cầu, hệ thống báo số lượng còn lại và không trừ tiền.
- Khi thanh toán giỏ hàng, mỗi sản phẩm trong giỏ có thể có quantity > 1; hệ thống tạo mỗi tài khoản thành một đơn hàng riêng để khách xem được từng credential.

## Hướng dẫn quản lý kho tài khoản cho Admin

Vào: `http://localhost/admin/crud/account_stock/list.php`

Các thao tác chính:

1. Xem thống kê kho
   - Tổng tài khoản
   - Còn hàng
   - Đã bán
   - Đã đặt

2. Lọc và tìm kiếm
   - Lọc theo sản phẩm
   - Lọc theo trạng thái
   - Tìm theo nội dung tài khoản hoặc tên sản phẩm

3. Thêm 1 tài khoản
   - Bấm `Thêm`
   - Chọn sản phẩm
   - Nhập JSON, ví dụ:
     `{"account":"user@example.com","password":"Pass123!"}`

4. Nhập hàng loạt
   - Bấm `Nhập hàng loạt`
   - Chọn sản phẩm
   - Có 2 cách:
     - Nhập số lượng để tạo tài khoản giả tự động
     - Hoặc dán nhiều dòng JSON, mỗi dòng là 1 tài khoản:
       `{"account":"user1@example.com","password":"Pass123!"}`
       `{"account":"user2@example.com","password":"Pass456!"}`

5. Quản lý nhiều tài khoản cùng lúc
   - Tick checkbox ở từng dòng
   - Có thể xóa hàng loạt
   - Có thể đặt lại trạng thái thành `Còn hàng`


## SePay auto-check nạp tiền

- Trang nạp tiền tạo yêu cầu pending trong 1 phút.
- Khi còn yêu cầu pending, trình duyệt tự gọi `/crud/api/deposit_poll.php` mỗi 3 giây.
- Backend gọi `GET https://my.sepay.vn/userapi/transactions/list` với headers:
  - `Content-Type: application/json`
  - `Authorization: Bearer <api_token>`
- Để tránh lỗi SePay `HTTP 429 Too Many Requests`, backend có file lock rate-limit và frontend chỉ poll 3 giây/lần.
- Giao dịch chỉ được cộng tiền khi khớp cả 2 điều kiện:
  1. `amount_in` bằng đúng số tiền yêu cầu nạp.
  2. `transaction_content` chứa đúng `unique_code`/nội dung chuyển khoản.
- Các trường ngân hàng khác chỉ lưu tham khảo; logic không phụ thuộc format riêng của từng ngân hàng.
- Nếu quá 1 phút chưa khớp giao dịch, yêu cầu nạp tự chuyển sang `cancelled`; user cần tạo mã QR mới.
