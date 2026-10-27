-- =====================================================
-- Vaccination Management System - Database (Day 1)
-- phpMyAdmin > Import tab se ye poori file import karo
-- =====================================================

CREATE DATABASE IF NOT EXISTS vaccination_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vaccination_db;

-- Purani tables hata do (dobara import karna asaan ho)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS hospital_suggestions, past_vaccinations, bookings, vaccines, children, hospitals, users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1) USERS: teeno roles ka login yahan hota hai
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,          -- hashed password
  phone VARCHAR(20),
  role ENUM('admin','parent','hospital') NOT NULL,
  status ENUM('active','pending','disabled') NOT NULL DEFAULT 'active',  -- hospital naya ho to pending, admin manzoor kare to active
  failed_logins INT NOT NULL DEFAULT 0,                                  -- galat password ki ginti (brute-force se bachao)
  locked_until DATETIME NULL,                                            -- is waqt tak login band
  city VARCHAR(80) NULL,                                                 -- parent ka shehar ("Near you" hospitals ke liye)
  address VARCHAR(255) NULL,                                             -- parent ka pata (optional)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2) HOSPITALS: hospital ki extra details
CREATE TABLE hospitals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,                         -- hospital ka login account
  hospital_name VARCHAR(150) NOT NULL,
  address VARCHAR(255) NOT NULL,
  location VARCHAR(100) NOT NULL,           -- city / area
  phone VARCHAR(20),
  license_no VARCHAR(60) NULL,                      -- hospital ka license / registration number (admin verify karta hai)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3) CHILDREN: parent ke bachon ki info
CREATE TABLE children (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parent_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  dob DATE NOT NULL,
  gender ENUM('male','female') NOT NULL,
  blood_group VARCHAR(5),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4) VACCINES: vaccine ki list
--    recommended_age_days: bache ki umar (din mein) jab ye lagni chahiye
--    (baad mein "auto-suggest" feature isi se chalega)
CREATE TABLE vaccines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vaccine_name VARCHAR(120) NOT NULL,
  description VARCHAR(255),
  recommended_age VARCHAR(50),              -- dikhane ke liye e.g. "6 weeks"
  recommended_age_days INT NOT NULL DEFAULT 0,
  availability ENUM('available','unavailable') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB;

-- 5) BOOKINGS: sab se important table
CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  child_id INT NOT NULL,
  parent_id INT NOT NULL,
  hospital_id INT NOT NULL,
  vaccine_id INT NOT NULL,
  appointment_date DATE NOT NULL,
  appointment_time TIME NOT NULL,
  approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  vaccination_status ENUM('pending','vaccinated','not_vaccinated') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (child_id)    REFERENCES children(id)  ON DELETE CASCADE,
  FOREIGN KEY (parent_id)   REFERENCES users(id)     ON DELETE CASCADE,
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
  FOREIGN KEY (vaccine_id)  REFERENCES vaccines(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6) PAST_VACCINATIONS: purani (pehle se lag chuki) vaccines jo parent khud darj kare, admin verify kare
CREATE TABLE past_vaccinations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  child_id INT NOT NULL,
  vaccine_id INT NOT NULL,
  given_date DATE NOT NULL,
  place VARCHAR(150) NOT NULL,                       -- kahan lagi (hospital/clinic ka naam)
  status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (child_id)   REFERENCES children(id) ON DELETE CASCADE,
  FOREIGN KEY (vaccine_id) REFERENCES vaccines(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_child_vaccine (child_id, vaccine_id)   -- ek bache ki ek vaccine ek hi baar
) ENGINE=InnoDB;

-- 7) HOSPITAL_SUGGESTIONS: parent ke batae hue naye hospitals; admin verify karke list mein jorta hai
CREATE TABLE hospital_suggestions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parent_id INT NOT NULL,
  hospital_name VARCHAR(150) NOT NULL,
  city VARCHAR(80) NOT NULL,
  address VARCHAR(255) NULL,
  phone VARCHAR(20) NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  hospital_id INT NULL,                              -- approve hone par kaunsa hospital bana (ya pehle se maujood tha)
  decided_at DATETIME NULL,                          -- admin ne kab faisla kiya
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parent_id)   REFERENCES users(id)     ON DELETE CASCADE,
  FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- DEMO DATA (taake charts aur reports khali na dikhein)
-- Admin   : admin@vaccine.com    / admin123
-- Parents : ali@example.com, sara@example.com   / 123456
-- Hospital: city@hospital.com, care@hospital.com / 123456
-- =====================================================

INSERT INTO users (id, name, email, password, phone, role) VALUES
(1, 'Admin',               'admin@vaccine.com',  '$2y$12$8oy9SbOdpuJIa4vcOH3OiOQfwxha78CXziCYvUh7odBQD1WMM72KK', '03000000000', 'admin'),
(2, 'Ali Khan',            'ali@example.com',    '$2y$12$9dwEw9WRwgH4f7dCSUL4dOvRYRWN7.nS4fN7SktujTPQhZhwRpMGm', '03011111111', 'parent'),
(3, 'Sara Ahmed',          'sara@example.com',   '$2y$12$9dwEw9WRwgH4f7dCSUL4dOvRYRWN7.nS4fN7SktujTPQhZhwRpMGm', '03022222222', 'parent'),
(4, 'City Hospital',       'city@hospital.com',  '$2y$12$9dwEw9WRwgH4f7dCSUL4dOvRYRWN7.nS4fN7SktujTPQhZhwRpMGm', '02111111111', 'hospital'),
(5, 'Care Children Clinic','care@hospital.com',  '$2y$12$9dwEw9WRwgH4f7dCSUL4dOvRYRWN7.nS4fN7SktujTPQhZhwRpMGm', '02122222222', 'hospital');

INSERT INTO hospitals (id, user_id, hospital_name, address, location, phone) VALUES
(1, 4, 'City Hospital',        'Main Shahrah-e-Faisal',  'Karachi', '02111111111'),
(2, 5, 'Care Children Clinic', 'Block 5, Clifton',       'Karachi', '02122222222'),
(3, NULL, 'Green Crescent Health Center', 'Gulshan-e-Iqbal', 'Karachi', '02133333333');

INSERT INTO children (id, parent_id, name, dob, gender, blood_group) VALUES
(1, 2, 'Hamza Ali',   DATE_SUB(CURDATE(), INTERVAL 3 MONTH),  'male',   'B+'),
(2, 2, 'Ayesha Ali',  DATE_SUB(CURDATE(), INTERVAL 14 MONTH), 'female', 'O+'),
(3, 3, 'Zain Sara',   DATE_SUB(CURDATE(), INTERVAL 1 MONTH),  'male',   'A+');

INSERT INTO vaccines (vaccine_name, description, recommended_age, recommended_age_days, availability) VALUES
('BCG',               'Tuberculosis se bachao',            'Birth',     0,   'available'),
('OPV 0 (Polio)',     'Polio ki pehli khuraak',            'Birth',     0,   'available'),
('Pentavalent 1',     'DPT, Hepatitis B, Hib',             '6 weeks',   42,  'available'),
('Pneumococcal 1',    'Pneumonia se bachao',               '6 weeks',   42,  'available'),
('Pentavalent 2',     'DPT, Hepatitis B, Hib (doosri)',    '10 weeks',  70,  'available'),
('Pentavalent 3',     'DPT, Hepatitis B, Hib (teesri)',    '14 weeks',  98,  'available'),
('IPV (Injectable Polio)', 'Polio injection',              '14 weeks',  98,  'unavailable'),
('Measles 1',         'Khasra se bachao',                  '9 months',  270, 'available'),
('Measles 2',         'Khasra (doosri khuraak)',           '15 months', 450, 'available'),
('Typhoid',           'Typhoid se bachao',                 '9 months',  270, 'available');

INSERT INTO bookings (child_id, parent_id, hospital_id, vaccine_id, appointment_date, appointment_time, approval_status, vaccination_status) VALUES
(1, 2, 1, 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY),  '10:00:00', 'approved', 'pending'),
(2, 2, 2, 9, DATE_SUB(CURDATE(), INTERVAL 10 DAY), '11:30:00', 'approved', 'vaccinated'),
(3, 3, 1, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY),  '09:00:00', 'approved', 'vaccinated'),
(3, 3, 2, 3, DATE_ADD(CURDATE(), INTERVAL 20 DAY), '12:00:00', 'pending',  'pending'),
(1, 2, 1, 1, DATE_SUB(CURDATE(), INTERVAL 60 DAY), '10:30:00', 'approved', 'not_vaccinated'),
(2, 2, 1, 10, DATE_ADD(CURDATE(), INTERVAL 6 DAY), '14:00:00', 'rejected', 'pending');

-- =====================================================
-- EXTRA DEMO DATA (Day 14): charts aur reports bharpoor dikhane ke liye
-- Naya parent: fatima@example.com / 123456
-- =====================================================

INSERT INTO users (id, name, email, password, phone, role) VALUES
(6, 'Fatima Noor', 'fatima@example.com', '$2y$12$9dwEw9WRwgH4f7dCSUL4dOvRYRWN7.nS4fN7SktujTPQhZhwRpMGm', '03033333333', 'parent');

INSERT INTO children (id, parent_id, name, dob, gender, blood_group) VALUES
(4, 6, 'Inaya Noor',   DATE_SUB(CURDATE(), INTERVAL 240 DAY), 'female', 'A-'),
(5, 6, 'Rayyan Noor',  DATE_SUB(CURDATE(), INTERVAL 730 DAY), 'male',   'B+'),
(6, 3, 'Maryam Sara',  DATE_SUB(CURDATE(), INTERVAL 180 DAY), 'female', 'O-');

INSERT INTO bookings (child_id, parent_id, hospital_id, vaccine_id, appointment_date, appointment_time, approval_status, vaccination_status) VALUES
-- Inaya (8 mahine)
(4, 6, 1, 1,  DATE_SUB(CURDATE(), INTERVAL 230 DAY), '09:00:00', 'approved', 'vaccinated'),
(4, 6, 1, 2,  DATE_SUB(CURDATE(), INTERVAL 230 DAY), '09:30:00', 'approved', 'vaccinated'),
(4, 6, 2, 3,  DATE_SUB(CURDATE(), INTERVAL 190 DAY), '10:00:00', 'approved', 'vaccinated'),
(4, 6, 2, 4,  DATE_SUB(CURDATE(), INTERVAL 190 DAY), '10:30:00', 'approved', 'vaccinated'),
(4, 6, 2, 5,  DATE_SUB(CURDATE(), INTERVAL 160 DAY), '11:00:00', 'approved', 'vaccinated'),
(4, 6, 1, 6,  DATE_SUB(CURDATE(), INTERVAL 100 DAY), '09:30:00', 'approved', 'vaccinated'),
(4, 6, 1, 8,  DATE_ADD(CURDATE(), INTERVAL 30 DAY),  '10:00:00', 'pending',  'pending'),
-- Rayyan (2 saal)
(5, 6, 1, 1,  DATE_SUB(CURDATE(), INTERVAL 700 DAY), '10:00:00', 'approved', 'vaccinated'),
(5, 6, 2, 8,  DATE_SUB(CURDATE(), INTERVAL 440 DAY), '11:30:00', 'approved', 'vaccinated'),
(5, 6, 2, 9,  DATE_SUB(CURDATE(), INTERVAL 75 DAY),  '12:00:00', 'approved', 'vaccinated'),
(5, 6, 1, 10, DATE_SUB(CURDATE(), INTERVAL 45 DAY),  '09:00:00', 'approved', 'not_vaccinated'),
-- Maryam (6 mahine)
(6, 3, 2, 1,  DATE_SUB(CURDATE(), INTERVAL 170 DAY), '09:00:00', 'approved', 'vaccinated'),
(6, 3, 1, 3,  DATE_SUB(CURDATE(), INTERVAL 110 DAY), '10:00:00', 'approved', 'vaccinated'),
(6, 3, 1, 5,  DATE_SUB(CURDATE(), INTERVAL 20 DAY),  '10:30:00', 'rejected', 'pending');

-- =====================================================
-- DEMO FLOW AID (Day 15): City Hospital ki aaj ki 2 approved appointments
-- Hospital demo (city@hospital.com) mein "Vaccinated" mark karne ke liye
-- =====================================================
INSERT INTO bookings (child_id, parent_id, hospital_id, vaccine_id, appointment_date, appointment_time, approval_status, vaccination_status) VALUES
(1, 2, 1, 5, CURDATE(), '10:00:00', 'approved', 'pending'),   -- Hamza, Pentavalent 2
(3, 3, 1, 4, CURDATE(), '11:30:00', 'approved', 'pending');   -- Zain, Pneumococcal 1

-- =====================================================
-- REALISTIC HISTORY (final): purane bachon ki zyadatar vaccines lag chuki hon,
-- taake dashboard par "overdue" ki ginti sirf chand (asli) vaccines ki ho.
-- Hamza ko jaan boojh kar "attention wala" bacha rakha hai (BCG miss, OPV 0 aur Pneumococcal 1 baqi).
-- =====================================================
INSERT INTO bookings (child_id, parent_id, hospital_id, vaccine_id, appointment_date, appointment_time, approval_status, vaccination_status) VALUES
-- Ayesha (14 mahine): zyadatar mukammal (IPV unavailable aur Typhoid baqi)
(2, 2, 2, 1, DATE_SUB(CURDATE(), INTERVAL 415 DAY), '09:00:00', 'approved', 'vaccinated'),
(2, 2, 2, 2, DATE_SUB(CURDATE(), INTERVAL 415 DAY), '09:30:00', 'approved', 'vaccinated'),
(2, 2, 2, 3, DATE_SUB(CURDATE(), INTERVAL 375 DAY), '10:00:00', 'approved', 'vaccinated'),
(2, 2, 2, 4, DATE_SUB(CURDATE(), INTERVAL 375 DAY), '10:30:00', 'approved', 'vaccinated'),
(2, 2, 1, 5, DATE_SUB(CURDATE(), INTERVAL 345 DAY), '11:00:00', 'approved', 'vaccinated'),
(2, 2, 1, 6, DATE_SUB(CURDATE(), INTERVAL 315 DAY), '11:30:00', 'approved', 'vaccinated'),
(2, 2, 1, 8, DATE_SUB(CURDATE(), INTERVAL 150 DAY), '12:00:00', 'approved', 'vaccinated'),
-- Rayyan (2 saal): mukammal (sirf IPV unavailable aur Typhoid miss)
(5, 6, 1, 2, DATE_SUB(CURDATE(), INTERVAL 700 DAY), '10:30:00', 'approved', 'vaccinated'),
(5, 6, 1, 3, DATE_SUB(CURDATE(), INTERVAL 660 DAY), '09:00:00', 'approved', 'vaccinated'),
(5, 6, 1, 4, DATE_SUB(CURDATE(), INTERVAL 660 DAY), '09:30:00', 'approved', 'vaccinated'),
(5, 6, 2, 5, DATE_SUB(CURDATE(), INTERVAL 630 DAY), '10:00:00', 'approved', 'vaccinated'),
(5, 6, 2, 6, DATE_SUB(CURDATE(), INTERVAL 600 DAY), '10:30:00', 'approved', 'vaccinated'),
-- Maryam (6 mahine)
(6, 3, 2, 2, DATE_SUB(CURDATE(), INTERVAL 170 DAY), '09:30:00', 'approved', 'vaccinated'),
(6, 3, 1, 4, DATE_SUB(CURDATE(), INTERVAL 110 DAY), '10:30:00', 'approved', 'vaccinated');

-- =====================================================
-- DAY 16 DEMO DATA
-- Sunrise Clinic: naya hospital jo admin ki manzoori ka intezar kar raha hai
--   (login: sunrise@hospital.com / 123456 -> "waiting for approval" ka message)
-- Purani vaccinations: ek pending (admin verify karega), ek verified
-- =====================================================
INSERT INTO users (id, name, email, password, phone, role, status) VALUES
(7, 'Sunrise Clinic', 'sunrise@hospital.com', '$2y$12$9dwEw9WRwgH4f7dCSUL4dOvRYRWN7.nS4fN7SktujTPQhZhwRpMGm', '02144444444', 'hospital', 'pending');

INSERT INTO hospitals (id, user_id, hospital_name, address, location, phone) VALUES
(4, 7, 'Sunrise Clinic', 'Block 2, North Nazimabad', 'Karachi', '02144444444');

INSERT INTO past_vaccinations (child_id, vaccine_id, given_date, place, status) VALUES
(1, 2, DATE_SUB(CURDATE(), INTERVAL 85 DAY),  'Local maternity clinic',   'pending'),
(6, 5, DATE_SUB(CURDATE(), INTERVAL 100 DAY), 'Care Children Clinic',     'verified');

-- =====================================================
-- DAY 18 DEMO DATA: shehar, pata aur license
-- Fatima Lahore mein hai: booking page par Lahore ka Green Crescent "Near you" (sab se upar) aayega
-- =====================================================
UPDATE users SET city = 'Karachi', address = 'Gulshan-e-Iqbal, Block 13' WHERE id = 2;
UPDATE users SET city = 'Karachi', address = 'North Nazimabad, Block H'  WHERE id = 3;
UPDATE users SET city = 'Lahore',  address = 'Model Town, Block C'       WHERE id = 6;

UPDATE hospitals SET license_no = 'KHI-H-0001' WHERE id = 1;
UPDATE hospitals SET license_no = 'KHI-H-0002' WHERE id = 2;
UPDATE hospitals SET license_no = 'LHR-H-0003', location = 'Lahore', address = 'Gulberg III, Main Boulevard' WHERE id = 3;
UPDATE hospitals SET license_no = 'KHI-H-0004' WHERE id = 4;

-- =====================================================
-- DAY 19 DEMO DATA: hospital suggestions
-- Sara ki ek request pending hai (admin ke "Hospital Requests" mein nazar aayegi);
-- Fatima ki request kal approve hui (uske dashboard par "good news" ka banner aayega)
-- =====================================================
INSERT INTO hospital_suggestions (parent_id, hospital_name, city, address, phone, status, hospital_id, decided_at) VALUES
(3, 'Indus Kids Clinic',            'Karachi', 'Clifton, Block 4',   '02155555555', 'pending',  NULL, NULL),
(6, 'Green Crescent Health Center', 'Lahore',  'Gulberg III',        NULL,          'approved', 3,    DATE_SUB(CURDATE(), INTERVAL 1 DAY));
