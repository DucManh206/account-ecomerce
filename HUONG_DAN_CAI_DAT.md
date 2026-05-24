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
Nếu dùng tài khoản MySQL khác mặc định, mở tệp tin `admin/config/db.php` và sửa thông số:
```php
$host = 'localhost';
$dbname = 'account_shop';
$username = 'web'; // Tài khoản MySQL (mặc định XAMPP thường là root)
$password = '123'; // Mật khẩu MySQL (mặc định XAMPP thường để trống '')
```

### Bước 4: Chạy thử hệ thống
* Giao diện mua hàng dành cho khách hàng: `http://localhost/account-ecomerce/index.php`
* Giao diện trang quản trị viên (Admin): `http://localhost/account-ecomerce/admin/login.php`

---

## 3. Tài khoản đăng nhập chạy thử

Sau khi đã nạp thành công database, bạn có thể dùng các tài khoản sau để test:

* **Tài khoản Quản trị viên (Admin)**:
  * Username: `admin`
  * Password: `admin123`

* **Tài khoản Thành viên (User)**:
  * Username: `khachhang`
  * Password: `123456` (hoặc `member` mật khẩu `123456`)
