CREATE DATABASE IF NOT EXISTS account_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE account_shop;

-- Drop existing tables in dependency order
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS accounts;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS categories;

-- Bảng users
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  fullname VARCHAR(100) NOT NULL,
  role ENUM('admin', 'user') DEFAULT 'user',
  balance DECIMAL(15,0) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin mặc định: admin / admin123 (mã hóa Bcrypt)
INSERT INTO users (username, password, fullname, role) VALUES
('admin', '$2y$10$8ADGZH8kDHdtRltgegXnK.IaVLfGACGC6fyK16sVJ.fJc32hg7AuS', 'Quản trị viên', 'admin');

-- Bảng categories
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (name, description) VALUES
('Game', 'Tài khoản game các loại (LMHT, Valorant, Steam, PUBG...)'),
('Streaming', 'Tài khoản xem phim, nghe nhạc (Netflix, Spotify, Apple Music...)'),
('Software', 'Tài khoản phần mềm (Office 365, Adobe, Windows...)'),
('Other', 'Các loại tài khoản khác');

-- Bảng accounts
CREATE TABLE accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  description TEXT,
  price DECIMAL(15,0) NOT NULL DEFAULT 0,
  category_id INT DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  account_detail TEXT DEFAULT NULL COMMENT 'Thông tin đăng nhập',
  status ENUM('available', 'sold') DEFAULT 'available',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
 'Tên đăng nhập: steam_acc1\nMật khẩu: steam_pass001\nEmail recovery: recover1@gmail.com\nGhi chú: Có steam guard mobile', 'available'),

('YouTube Premium - 1 năm', 'Tài khoản YouTube Premium xem phim, nghe nhạc không quảng cáo, chất lượng Full HD, 1 năm', 150000, 2,
 'Email: yt_acc1@gmail.com\nMật khẩu: yt_pass123\nHạn sử dụng: 12 tháng\nLoại: Cá nhân', 'available'),

('ChatGPT Plus - 1 tháng', 'Tài khoản ChatGPT Plus (GPT-4o) không giới hạn lượt chat, tạo ảnh DALL-E, thời hạn 1 tháng', 80000, 3,
 'Email: gpt_plus_acc1@gmail.com\nMật khẩu: gpt_pass456\nHạn sử dụng: 30 ngày', 'available'),

('Minecraft Premium Full Access', 'Tài khoản Minecraft Premium bản quyền chơi được mọi server Hypixel, hỗ trợ đổi thông tin email', 290000, 1,
 'Email: mc_acc1@gmail.com\nMật khẩu: mc_pass789\nGhi chú: Hỗ trợ đổi email trên Mojang', 'available'),

('Canva Pro - 1 năm', 'Tài khoản Canva Pro gói thiết kế không giới hạn font chữ, hình ảnh cao cấp, thời hạn 1 năm', 120000, 3,
 'Email: canva_acc1@gmail.com\nMật khẩu: cv_pass123\nLoại: Nhóm Edu Premium', 'available');

-- Users mặc định (mã hóa Bcrypt)
INSERT INTO users (username, password, fullname, balance) VALUES
('member', '$2y$10$O5LWmKFO.SdalD94XiOnE.tX6ENhMEr2GXQR5AWT3om1suK771W1y', 'Nguyễn Văn Thành viên', 500000),
('khachhang', '$2y$10$5D4eoHaRJ9LnpSBq80Qh8uDI6G20bgp.jqxz2bzHBjCQo.UuVkXay', 'Trần Thị Khách Hàng', 150000);

-- Bảng orders
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  account_id INT NOT NULL,
  price DECIMAL(15,0) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE accounts SET status = 'sold' WHERE id IN (4, 5);

INSERT INTO orders (user_id, account_id, price) VALUES
(2, 4, 200000),
(3, 5, 500000);

-- Bảng sepay_transactions
CREATE TABLE IF NOT EXISTS sepay_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sepay_transaction_id VARCHAR(100) NOT NULL UNIQUE,
  user_id INT NOT NULL,
  amount DECIMAL(15,0) NOT NULL,
  transaction_date DATETIME NOT NULL,
  content VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng settings (cấu hình hệ thống)
CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value) VALUES
('sepay_api_token', 'YOUR_SEPAY_API_TOKEN'),
('sepay_bank_code', 'MBBank'),
('sepay_bank_num', '0398687777'),
('sepay_bank_name', 'NGUYEN VAN A'),
('sepay_memo_prefix', 'NAP');
