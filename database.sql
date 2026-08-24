-- ============================================
--  MediBook Database Schema
--  Import this file via phpMyAdmin
-- ============================================

CREATE DATABASE IF NOT EXISTS medibook;
USE medibook;

-- ─────────────────────────────────────────
--  USERS
-- ─────────────────────────────────────────
CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)        NOT NULL,
    email           VARCHAR(150)        NOT NULL UNIQUE,
    password        VARCHAR(255)        NOT NULL,
    phone           VARCHAR(15)         DEFAULT NULL,
    is_subscribed   TINYINT(1)          DEFAULT 0,
    subscribed_at   DATETIME            DEFAULT NULL,
    created_at      DATETIME            DEFAULT CURRENT_TIMESTAMP
);

-- ─────────────────────────────────────────
--  HOSPITALS
-- ─────────────────────────────────────────
CREATE TABLE hospitals (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)    NOT NULL,
    city        VARCHAR(100)    NOT NULL,
    address     VARCHAR(255)    DEFAULT NULL
);

-- ─────────────────────────────────────────
--  DOCTORS
-- ─────────────────────────────────────────
CREATE TABLE doctors (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)        NOT NULL,
    speciality      VARCHAR(100)        NOT NULL,
    experience      INT                 NOT NULL,
    rating          DECIMAL(2,1)        DEFAULT 0.0,
    review_count    INT                 DEFAULT 0,
    expertise       TEXT                DEFAULT NULL,
    about           TEXT                DEFAULT NULL,
    hospital_id     INT                 DEFAULT NULL,
    phone           VARCHAR(15)         DEFAULT NULL,
    email           VARCHAR(150)        DEFAULT NULL,
    available       TINYINT(1)          DEFAULT 1,
    created_at      DATETIME            DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL
);

-- ─────────────────────────────────────────
--  SUBSCRIPTION PLANS
-- ─────────────────────────────────────────
CREATE TABLE plans (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50)     NOT NULL,
    price       DECIMAL(8,2)    NOT NULL,
    duration    INT             NOT NULL COMMENT 'duration in days',
    features    TEXT            DEFAULT NULL
);

-- ─────────────────────────────────────────
--  APPOINTMENTS
-- ─────────────────────────────────────────
CREATE TABLE appointments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT             NOT NULL,
    doctor_id       INT             NOT NULL,
    appt_date       DATE            NOT NULL,
    appt_time       TIME            NOT NULL,
    status          ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    created_at      DATETIME        DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────
--  SEED DATA — HOSPITALS
-- ─────────────────────────────────────────
INSERT INTO hospitals (name, city, address) VALUES
('City Care Hospital',      'Bengaluru', '12, MG Road, Bengaluru'),
('HealthPlus Clinic',       'Chennai',   '45, Anna Salai, Chennai'),
('Apollo Clinic',           'Hyderabad', '78, Banjara Hills, Hyderabad'),
('Manipal Hospital',        'Bengaluru', '98, Whitefield, Bengaluru'),
('Fortis Healthcare',       'Delhi',     '56, Shalimar Bagh, Delhi');

-- ─────────────────────────────────────────
--  SEED DATA — DOCTORS
-- ─────────────────────────────────────────
INSERT INTO doctors (name, speciality, experience, rating, review_count, expertise, about, hospital_id, phone, email, available) VALUES
('Dr. Arun Kumar',    'Cardiologist',      12, 4.9, 312, 'Heart Surgery, Angioplasty, ECG',         'Dr. Arun Kumar is a senior cardiologist with over 12 years of experience in interventional cardiology and heart failure management.',       1, '9876543210', 'arun@citycare.com',    1),
('Dr. Meera Sharma',  'Dermatologist',      8, 4.8, 198, 'Acne Treatment, Skin Laser, Hair Loss',   'Dr. Meera Sharma specialises in cosmetic and medical dermatology with a focus on evidence-based treatments.',                             2, '9876543211', 'meera@healthplus.com', 1),
('Dr. Rahul Verma',   'Orthopedic Surgeon',10, 4.7, 276, 'Joint Replacement, Sports Injury, Spine', 'Dr. Rahul Verma is a leading orthopedic surgeon with expertise in minimally invasive joint replacement procedures.',                    3, '9876543212', 'rahul@apollo.com',     1),
('Dr. Sneha Patel',   'Pediatrician',       9, 4.9,  89, 'Child Nutrition, Vaccinations, Asthma',   'Dr. Sneha Patel is a compassionate pediatrician dedicated to the health and development of children from birth through adolescence.',    4, '9876543213', 'sneha@manipal.com',    1),
('Dr. Anjali Reddy',  'Neurologist',        11, 4.7, 154, 'Epilepsy, Migraine, Stroke Management',  'Dr. Anjali Reddy is a neurologist with special interest in epilepsy management and neurodegenerative disorders.',                        5, '9876543214', 'anjali@fortis.com',    1),
('Dr. Vikram Singh',  'General Physician',   7, 4.5, 210, 'Diabetes, Hypertension, Fever',          'Dr. Vikram Singh is a trusted general physician providing comprehensive primary care for patients of all ages.',                         1, '9876543215', 'vikram@citycare.com',  1),
('Dr. Kavya Nair',    'Gynecologist',        9, 4.8, 175, 'Pregnancy, PCOS, Laparoscopy',            'Dr. Kavya Nair is a skilled gynecologist and obstetrician with expertise in high-risk pregnancies and minimally invasive surgeries.',    2, '9876543216', 'kavya@healthplus.com', 0),
('Dr. Rohan Das',     'ENT Specialist',     10, 4.6, 132, 'Sinusitis, Hearing Loss, Tonsils',        'Dr. Rohan Das is an ENT specialist with extensive experience in both surgical and non-surgical treatments of ear, nose, and throat.',    3, '9876543217', 'rohan@apollo.com',     1);

-- ─────────────────────────────────────────
--  SEED DATA — PLANS
-- ─────────────────────────────────────────
INSERT INTO plans (name, price, duration, features) VALUES
('Monthly',  199.00,  30,  'Book appointments, View doctor contacts, View hospital details, Appointment history'),
('Yearly',   999.00,  365, 'Book appointments, View doctor contacts, View hospital details, Appointment history, Priority support');
