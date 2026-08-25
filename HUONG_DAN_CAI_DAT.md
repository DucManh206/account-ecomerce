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
Ứng dụng đọc cấu hình từ các biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` và `DB_PASS`. Với XAMPP mặc định, có thể dùng tài khoản `root` không mật khẩu; trên môi trường thật phải tạo tài khoản MySQL riêng và đặt biến môi trường tương ứng. Không lưu mật khẩu thật trong mã nguồn.

### Bước 4: Chạy thử hệ thống
* Giao diện mua hàng dành cho khách hàng: `http://localhost/account-ecomerce/index.php`
* Giao diện đăng nhập hệ thống: `http://localhost/account-ecomerce/login.php`
  *(Đăng nhập tài khoản quyền Admin sẽ tự động chuyển hướng vào trang quản trị `admin/dashboard.php`)*

---

## 3. Hoặc chạy Toàn Bộ Dự Án bằng Docker (Full-Stack Tự Động 100%)

Nếu máy tính đã cài đặt **Docker**:

1. Sao chép `.env.example` thành `.env`, sau đó đổi toàn bộ mật khẩu mẫu trong `.env`.
2. Mở Terminal/PowerShell tại thư mục dự án và chạy lệnh:
   ```bash
   docker compose up -d --build
   ```
3. Docker sẽ tự động xây dựng và khởi chạy 3 dịch vụ:
   * 🌐 **Ứng dụng Web PHP 8.2 + Apache**: `http://localhost:8000/index.php`
   * 🔐 **Trang đăng nhập Quản trị & Thành viên**: `http://localhost:8000/login.php`
   * 🗄️ **Cơ sở dữ liệu MySQL 8.0**: Cổng `3306` (Tự động nạp dữ liệu từ `database.sql`)
   * 📊 **Giao diện Quản lý Database phpMyAdmin**: `http://localhost:8080` (dùng `DB_USER` và `DB_PASSWORD` trong `.env`)

4. Khi sửa đổi code ở máy tính của bạn, web trong Docker sẽ tự động cập nhật ngay lập tức (nhờ cơ chế Live Volume Mount).

5. Dừng hệ thống khi không sử dụng:
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
