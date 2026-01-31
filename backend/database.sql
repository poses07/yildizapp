-- Veritabanı Oluşturma
CREATE DATABASE IF NOT EXISTS yildizapp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE yildizapp_db;

-- Yönetici Tablosu
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Varsayılan Yönetici (Şifre: admin123)
-- Not: Gerçek kullanımda şifreler hashlenmelidir (MD5 veya BCrypt).
-- Buraya basitlik için düz metin veya basit hash koyabiliriz, PHP tarafında md5() kullanacağız.
INSERT INTO admin_users (username, password) VALUES ('admin', '0192023a7bbd73250516f069df18b500'); -- admin123 MD5

-- Uygulama Ayarları (Dinamik Yönetim İçin)
CREATE TABLE IF NOT EXISTS app_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value VARCHAR(255) NOT NULL,
    description VARCHAR(255)
);

-- Varsayılan Ayarlar
INSERT INTO app_settings (setting_key, setting_value, description) VALUES 
('base_fare', '25.0', 'Açılış Ücreti (TL)'),
('price_per_km', '15.0', 'KM Başına Ücret (TL)'),
('min_fare', '50.0', 'Minimum İndi-Bindi Ücreti (TL)'),
('app_mode', 'active', 'Uygulama Modu (active/maintenance)');

-- Kullanıcılar Tablosu
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sürücüler Tablosu
CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, -- Users tablosuna referans olabilir veya ayrı tutulabilir
    car_model VARCHAR(100),
    plate_number VARCHAR(20),
    is_online TINYINT(1) DEFAULT 0,
    current_lat DOUBLE,
    current_lng DOUBLE,
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Yolculuklar Tablosu
CREATE TABLE IF NOT EXISTS rides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    driver_id INT,
    pickup_address VARCHAR(255),
    destination_address VARCHAR(255),
    pickup_lat DOUBLE,
    pickup_lng DOUBLE,
    dest_lat DOUBLE,
    dest_lng DOUBLE,
    status ENUM('pending', 'accepted', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    fare DOUBLE,
    distance_km DOUBLE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
