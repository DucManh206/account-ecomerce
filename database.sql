-- ============================================================
-- DATABASE: account_shop
-- Shop bán tài khoản - Nhóm 5
-- ============================================================

CREATE DATABASE IF NOT EXISTS account_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE account_shop;

-- ============================================================
-- BẢNG: admin_users (người quản trị)
-- ============================================================
CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  fullname VARCHAR(100) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mật khẩu mặc định: admin123 (mã hóa MD5)
INSERT INTO admin_users (username, password, fullname) VALUES
('admin', MD5('admin123'), 'Quản trị viên');

-- ============================================================
-- BẢNG: categories (danh mục tài khoản)
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categories (name, description) VALUES
('Game', 'Tài khoản game các loại (LMHT, Valorant, Steam, PUBG...)'),
('Streaming', 'Tài khoản xem phim, nghe nhạc (Netflix, Spotify, Apple Music...)'),
('Software', 'Tài khoản phần mềm (Office 365, Adobe, Windows...)'),
('Other', 'Các loại tài khoản khác');

-- ============================================================
-- BẢNG: accounts (tài khoản cần bán)
-- ============================================================
CREATE TABLE IF NOT EXISTS accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  description TEXT,
  price DECIMAL(15,0) NOT NULL DEFAULT 0,
  category_id INT DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  account_detail TEXT DEFAULT NULL COMMENT 'Thông tin đăng nhập tài khoản',
  status ENUM('available', 'sold') DEFAULT 'available',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO accounts (name, description, price, category_id, account_detail, status) VALUES
('Acc LMHT - Bạch Kim 1', 'Rank Bạch Kim 1, 150+ skin, full tướng thường, có 20 tướng LMHT:TFT', 250000, 1,
 'Tên đăng nhập: lol_acc1\nMật khẩu: abc123\nEmail: lol1@gmail.com\nGhi chú: Đã link SMS bảo vệ', 'available'),

('Acc Valorant - Vàng 2', 'Rank Vàng 2, 35 skin, battlepass mùa hiện tại, điểm rank 75', 180000, 1,
 'Tên đăng nhập: val_acc1\nMật khẩu: xyz789\nEmail: val1@gmail.com\nGhi chú: Chưa link số điện thoại', 'available'),

('Netflix 4K - 6 tháng', 'Gói Ultra HD 4K, xem được trên 4 thiết bị cùng lúc, thời hạn 6 tháng', 350000, 2,
 'Email: netflix_acc1@gmail.com\nMật khẩu: nf_pass123\nHạn sử dụng: 6 tháng kể từ ngày kích hoạt\nHồ sơ: 5 profile', 'available'),

('Spotify Premium - 1 năm', 'Spotify Premium cá nhân, nghe nhạc không quảng cáo, tải nhạc offline, 1 năm', 200000, 2,
 'Email: spoti_acc1@gmail.com\nMật khẩu: sp_pass456\nHạn sử dụng: 12 tháng\nLoại: Individual', 'available'),

('Office 365 Personal - 1 năm', 'Microsoft Office 365 Personal, 1 PC/Mac + 1 tablet, đầy đủ Word Excel PowerPoint', 500000, 3,
 'Email: office_acc1@outlook.com\nMật khẩu: off_pass789\nProduct Key: XXXXX-YYYYY-ZZZZZ-WWWWW-VVVVV\nHạn: 12 tháng', 'available'),

('Acc Steam - 50 Game', 'Steam account với 50 tựa game bao gồm: CS2, Dota 2, PUBG, GTA V, Elden Ring...', 450000, 1,
 'Tên đăng nhập: steam_acc1\nMật khẩu: steam_pass001\nEmail recovery: recover1@gmail.com\nGhi chú: Có steam guard mobile', 'available');
