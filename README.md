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
http://localhost/migrations/setup.php
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
3. Chạy `https://yourdomain.com/migrations/setup.php` một lần duy nhất
4. **XÓA** hoặc **đổi tên** thư mục `migrations/` sau khi setup xong để tránh bị chạy lại
5. Đổi mật khẩu admin mặc định
6. Bật HTTPS (Let's Encrypt hoặc SSL hosting)

---

## License

Dự án dành cho mục đích học tập và cá nhân.
