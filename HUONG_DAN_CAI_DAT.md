# Hướng dẫn cài đặt & sử dụng - AccountShop

Tài liệu này hướng dẫn cách cấu hình, khởi chạy cơ sở dữ liệu và sử dụng các tính năng cơ bản của dự án AccountShop (Nhóm 5).

---

## 1. Yêu cầu hệ thống

* PHP >= 8.0 (khuyên dùng PHP 8.2)
* MySQL / MariaDB (thông qua XAMPP, Laragon...)
* Apache Web Server

---

## 2. Các bước cài đặt nhanh

### Bước 1: Copy mã nguồn
Copy thư mục dự án vào thư mục chạy web của bạn:
* XAMPP: `C:\xampp\htdocs\<ten_thu_muc_du_an>`
* Laragon: `C:\laragon\www\<ten_thu_muc_du_an>`

### Bước 2: Cấu hình kết nối Cơ sở dữ liệu
Mở file `admin/config/db.php` để kiểm tra hoặc cập nhật thông số kết nối:
```php
$host = 'localhost';
$dbname = 'account_shop';
$username = 'web'; // User MySQL
$password = '123'; // Password MySQL
```

### Bước 3: Khởi tạo database

---

Truy cập `phpMyAdmin`, tạo mới database tên `account_shop`. Sau đó chọn Import file `database.sql` nằm ở thư mục gốc của dự án.

---

## 3. Danh sách tài khoản thử nghiệm

Sau khi khởi tạo CSDL, bạn sử dụng các tài khoản sau để đăng nhập chạy thử:

| Vai trò | Tên đăng nhập (Username) | Mật khẩu (Password) |
| :--- | :--- | :--- |
| **Quản trị viên (Admin)** | `admin` | `admin123` |
| **Khách hàng (User)** | `khachhang1` | `123456` |
| **Khách hàng (User)** | `khachhang2` | `123456` |

---

## 4. Hướng dẫn sử dụng chức năng chính

### Giao diện Quản trị (Admin Dashboard)
* **Đường dẫn**: `http://localhost/<ten_thu_muc_du_an>/admin/login.php` (hoặc click nút "Quản trị viên" trên navbar sau khi đã đăng nhập tài khoản admin ở frontend).
* **Các chức năng chính**:
  * **Trạng thái hệ thống**: Hiển thị nhanh kết nối CSDL, số lượng hàng đang bán và múi giờ.
  * **Quản lý tài khoản đăng nhập (Bảo mật)**:
    * Khi thêm/sửa tài khoản, admin có thể dùng khung điền thông tin tự động trực quan để nhập Email, Mật khẩu, Hạn dùng, Ghi chú vào các ô riêng biệt. Hệ thống sẽ tự biên dịch và điền vào ô thông tin chi tiết lớn.
  * **Quản lý thành viên**:
    * Cho phép cập nhật họ tên, điều chỉnh số dư tài khoản của khách hàng.
    * Hỗ trợ thăng cấp/hạ cấp vai trò trực tiếp giữa **Khách hàng (User)** và **Quản trị viên (Admin)** thông qua menu lựa chọn.
    * Khi tạo mới thành viên, mật khẩu sẽ được tự động mã hóa bằng cơ chế Bcrypt.
  * **Nhật ký giao dịch**: Hiển thị danh sách 5 đơn hàng mới nhất trên hệ thống.

### Giao diện Khách hàng (Frontend)
* **Đường dẫn**: `http://localhost/<ten_thu_muc_du_an>/index.php`
* **Các chức năng chính**:
  * **Xem và lọc tài khoản**: Lọc nhanh các tài khoản đang bán theo từng danh mục phân loại.
  * **Giỏ hàng & Thanh toán**: Thêm sản phẩm vào giỏ, cập nhật số lượng và mua hàng trực tiếp trừ vào số dư tài khoản.
  * **Trang cá nhân & Lịch sử mua**:
    * Xem lại thông tin đăng nhập của các tài khoản đã mua.
    * Sử dụng nút "Sao chép" nhanh để copy thông tin đăng nhập mà không cần bôi đen thủ công.
  * **Nạp tiền tài khoản tự động (Tích hợp SePay & VietQR)**:
    * Khách hàng chọn/nhập số tiền cần nạp, hệ thống sử dụng API của VietQR để tự động tạo mã QR có số tiền và nội dung chuyển khoản tương ứng.
    * Hệ thống sử dụng cơ chế polling tự động truy vấn danh sách giao dịch qua cổng SePay API ở chế độ ngầm cứ mỗi 4 giây để cộng số dư cho thành viên ngay khi nhận được tiền.
    * Tích hợp chế độ chạy thử (Mock Mode) tự động nhận diện khi chưa thiết lập API Token thực tế để giả lập cộng tiền khi bấm "Kiểm tra giao dịch", giúp chấm điểm/thuyết trình đồ án vô cùng dễ dàng.
  * **Nút điều hướng nhanh cho Admin**: Xuất hiện nút "Quản trị viên" trên navbar ở tất cả các trang khách hàng khi có session admin đang hoạt động.
