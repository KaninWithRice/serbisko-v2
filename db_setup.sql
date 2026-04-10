CREATE DATABASE IF NOT EXISTS serbisko;
CREATE USER IF NOT EXISTS 'serbisko_user'@'localhost' IDENTIFIED BY 'serbisko_password';
GRANT ALL PRIVILEGES ON serbisko.* TO 'serbisko_user'@'localhost';
FLUSH PRIVILEGES;
