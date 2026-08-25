# Hướng dẫn cài đặt và chạy đồ án - AccountShop (Nhóm 5)

Tài liệu ngắn gọn hướng dẫn chạy thử dự án AccountShop cục bộ trên máy tính.

---

## 1. Chuẩn bị môi trường
* Đã cài sẵn phần mềm **XAMPP** (hoặc Laragon) hỗ trợ chạy PHP và MySQL.
* Khuyên dùng phiên bản PHP từ 8.0 trở lên.

---

## 2. Các bước cài đặt và chạy

### Bước 1: Copy mã nguồn
Giải nén và copy toàn bộ thư mục dự án `account-ecomerce` vào thư mục:
* `C:\xampp\htdocs\account-ecomerce` (nếu dùng XAMPP mặc định)

### Bước 2: Tạo Cơ sở dữ liệu và Nhập dữ liệu
1. Mở phần mềm XAMPP, nhấn **Start** ở cả 2 cổng **Apache** và **MySQL**.
2. Truy cập đường dẫn quản trị database: `http://localhost/phpmyadmin/`
3. Nhấp chọn **Mới** (New) để tạo cơ sở dữ liệu mới với tên: `account_shop`
4. Chọn cơ sở dữ liệu `account_shop` vừa tạo, nhấp chọn thẻ **Nhập** (Import).
5. Chọn tệp tin `database.sql` trong thư mục gốc của dự án và nhấn **Nhập** (Import) để hoàn thành.

### Bước 3: Cấu hình kết nối CSDL
Mở tệp tin `admin/config/db.php` và điều chỉnh thông số kết nối MySQL phù hợp với máy của bạn:
```php
$host = 'localhost';
$dbname = 'account_shop';
$username = 'web'; // Tài khoản MySQL (mặc định XAMPP thường là 'root')
$password = '123'; // Mật khẩu MySQL (mặc định XAMPP thường để trống '')
```
*(Nếu dùng XAMPP mặc định, đổi `$username = 'root';` và `$password = '';`)*

### Bước 4: Chạy thử hệ thống
* Giao diện mua hàng dành cho khách hàng: `http://localhost/account-ecomerce/index.php`
* Giao diện đăng nhập hệ thống: `http://localhost/account-ecomerce/login.php`
  *(Đăng nhập tài khoản quyền Admin sẽ tự động chuyển hướng vào trang quản trị `admin/dashboard.php`)*

---

## 3. Hoặc chạy Cơ sở dữ liệu bằng Docker (Nhanh & Tự động 100%)

Nếu máy tính đã cài đặt **Docker**, bạn không cần cài MySQL hay import database thủ công:

1. Mở Terminal/PowerShell tại thư mục dự án và chạy lệnh:
   ```bash
   docker compose up -d
   ```
2. Docker sẽ tự động:
   - Khởi tạo MySQL 8.0 trên cổng `3306` (Tài khoản: `web` / Mật khẩu: `123`, DB: `account_shop`).
   - Tự động nạp toàn bộ cấu trúc & dữ liệu từ file `database.sql`.
   - Khởi động giao diện quản trị phpMyAdmin tại: `http://localhost:8080` (Đăng nhập: `web` / `123` hoặc `root` / `123`).

3. Dừng Docker khi không dùng:
   ```bash
   docker compose down
   ```

---

## 4. Tài khoản đăng nhập chạy thử

Sau khi đã nạp thành công database, bạn có thể dùng các tài khoản sau để test:

* **Tài khoản Quản trị viên (Admin)**:
  * Username: `admin`
  * Password: `admin123`

* **Tài khoản Thành viên (User)**:
  * Username: `khachhang`
  * Password: `123456` (hoặc `member` mật khẩu `123456`)
