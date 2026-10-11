-- SAHAYATRI Database Schema & Seed Data
-- Multi-Tenant SaaS Database Architecture for Smart Tourism Safety & Travel Companion

CREATE DATABASE IF NOT EXISTS `sahayatri_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sahayatri_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `organization_broadcasts`;
DROP TABLE IF EXISTS `fair_prices`;
DROP TABLE IF EXISTS `reports`;
DROP TABLE IF EXISTS `emergency_resources`;
DROP TABLE IF EXISTS `sos_alerts`;
DROP TABLE IF EXISTS `safety_alerts`;
DROP TABLE IF EXISTS `safety_zones`;
DROP TABLE IF EXISTS `user_locations`;
DROP TABLE IF EXISTS `team_members`;
DROP TABLE IF EXISTS `teams`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `accommodations`;
DROP TABLE IF EXISTS `packages`;
DROP TABLE IF EXISTS `tourist_places`;
DROP TABLE IF EXISTS `destinations`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `organizations`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table: organizations (Tenants)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `organizations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) UNIQUE NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `logo` VARCHAR(255) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `status` ENUM('ACTIVE', 'SUSPENDED', 'PENDING') DEFAULT 'ACTIVE',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT DEFAULT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) UNIQUE NOT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin', 'organization_admin', 'user') NOT NULL DEFAULT 'user',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('ACTIVE', 'SUSPENDED') DEFAULT 'ACTIVE',
    `emergency_contact_name` VARCHAR(100) DEFAULT NULL,
    `emergency_contact_phone` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: destinations / regions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `destinations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT DEFAULT NULL,
    `name` VARCHAR(150) NOT NULL,
    `region` VARCHAR(100) NOT NULL,
    `category` ENUM('Historical', 'Religious', 'Natural', 'Adventure', 'Cultural', 'Wildlife', 'Trekking', 'Heritage', 'Family') DEFAULT 'Natural',
    `description` TEXT NOT NULL,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `best_season` VARCHAR(100) DEFAULT 'September - November, March - May',
    `safety_level` ENUM('SAFE', 'CAUTION', 'HIGH', 'CRITICAL') DEFAULT 'SAFE',
    `is_featured` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: tourist_places
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tourist_places` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `destination_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `category` VARCHAR(100) DEFAULT 'Attraction',
    `description` TEXT,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `entry_fee` VARCHAR(100) DEFAULT 'Free',
    `opening_hours` VARCHAR(100) DEFAULT '24 Hours',
    `rating` DECIMAL(3, 2) DEFAULT 4.50,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`destination_id`) REFERENCES `destinations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: tour_packages
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `packages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL,
    `destination_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `duration_days` INT NOT NULL,
    `price_npr` DECIMAL(10, 2) NOT NULL,
    `inclusions` TEXT,
    `exclusions` TEXT,
    `itinerary_summary` TEXT,
    `max_capacity` INT DEFAULT 15,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    `approval_status` ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING' COMMENT 'Superadmin approval gate',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`destination_id`) REFERENCES `destinations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: accommodations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `accommodations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT DEFAULT NULL,
    `destination_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `type` ENUM('Hotel', 'Resort', 'Homestay', 'Guesthouse', 'Lodge', 'Hostel') DEFAULT 'Hotel',
    `description` TEXT,
    `address` TEXT NOT NULL,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `price_per_night` DECIMAL(10, 2) NOT NULL,
    `rating` DECIMAL(3, 2) DEFAULT 4.80,
    `trust_score` INT DEFAULT 95,
    `is_verified` TINYINT(1) DEFAULT 1,
    `approval_status` ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING' COMMENT 'Superadmin approval gate',
    `facilities` TEXT,
    `contact_phone` VARCHAR(50),
    `image_url` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`destination_id`) REFERENCES `destinations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: bookings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_code` VARCHAR(50) UNIQUE NOT NULL,
    `user_id` INT NOT NULL,
    `package_id` INT DEFAULT NULL,
    `accommodation_id` INT DEFAULT NULL,
    `travel_date` DATE NOT NULL,
    `travelers_count` INT DEFAULT 1,
    `total_price` DECIMAL(10, 2) NOT NULL,
    `status` ENUM('PENDING', 'CONFIRMED', 'CANCELLED', 'COMPLETED') DEFAULT 'CONFIRMED',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`accommodation_id`) REFERENCES `accommodations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: teams
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `teams` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `team_code` VARCHAR(20) UNIQUE NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `leader_id` INT NOT NULL,
    `destination_name` VARCHAR(150) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `status` ENUM('ACTIVE', 'DISBANDED') DEFAULT 'ACTIVE',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`leader_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: team_members
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `team_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `team_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `sharing_option` ENUM('ALWAYS', 'TRIP_ONLY', 'EMERGENCY_ONLY', 'OFF') DEFAULT 'ALWAYS',
    `status` ENUM('ACTIVE', 'LEFT', 'REMOVED') DEFAULT 'ACTIVE',
    UNIQUE KEY `team_user` (`team_id`, `user_id`),
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: team_messages (Group Chat within Travel Teams)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `team_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `team_id` INT NOT NULL,
    `sender_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: messages (Direct Chat between Travelers and Travel Agencies)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT NOT NULL,
    `receiver_id` INT DEFAULT NULL,
    `organization_id` INT DEFAULT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: user_locations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_locations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `accuracy` DECIMAL(8, 2) DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_loc` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: safety_zones
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `safety_zones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT DEFAULT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `radius_meters` INT DEFAULT 500,
    `risk_level` ENUM('SAFE', 'CAUTION', 'HIGH', 'CRITICAL') DEFAULT 'CAUTION',
    `risk_type` ENUM('Landslide', 'Flood', 'Avalanche', 'Road accident', 'Wildlife', 'Restricted area', 'Heavy rainfall', 'Construction', 'General danger') DEFAULT 'General danger',
    `start_date` DATETIME DEFAULT NULL,
    `end_date` DATETIME DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: safety_alerts
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `safety_alerts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `zone_id` INT DEFAULT NULL,
    `user_id` INT NOT NULL,
    `team_id` INT DEFAULT NULL,
    `risk_level` ENUM('SAFE', 'CAUTION', 'HIGH', 'CRITICAL') NOT NULL,
    `message` TEXT NOT NULL,
    `latitude` DECIMAL(10, 8) DEFAULT NULL,
    `longitude` DECIMAL(11, 8) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`zone_id`) REFERENCES `safety_zones`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: sos_alerts
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sos_alerts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `team_id` INT DEFAULT NULL,
    `category` ENUM('Medical', 'Accident', 'Lost', 'Harassment', 'Theft', 'Natural disaster', 'Other') DEFAULT 'Medical',
    `latitude` DECIMAL(10, 8) DEFAULT NULL,
    `longitude` DECIMAL(11, 8) DEFAULT NULL,
    `note` TEXT,
    `status` ENUM('ACTIVE', 'IN_PROGRESS', 'RESOLVED', 'FALSE_ALARM') DEFAULT 'ACTIVE',
    `resolved_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `resolved_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`resolved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: emergency_resources
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `emergency_resources` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `type` ENUM('Hospital', 'Police', 'Ambulance', 'Tourist Police', 'Rescue Center', 'Fire Station', 'Embassy') NOT NULL,
    `phone` VARCHAR(50) NOT NULL,
    `alternate_phone` VARCHAR(50) DEFAULT NULL,
    `address` TEXT NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `is_verified` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: reports
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category` ENUM('Overcharging', 'Fake Guide', 'Scam', 'Unsafe Service', 'Lost Item', 'Harassment', 'Poor Service', 'Other') NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `location_name` VARCHAR(150) NOT NULL,
    `latitude` DECIMAL(10, 8) DEFAULT NULL,
    `longitude` DECIMAL(11, 8) DEFAULT NULL,
    `service_name` VARCHAR(150) DEFAULT NULL,
    `status` ENUM('PENDING', 'UNDER_REVIEW', 'RESOLVED', 'REJECTED') DEFAULT 'PENDING',
    `admin_notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: fair_prices
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fair_prices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `service_category` VARCHAR(100) NOT NULL,
    `route_or_item` VARCHAR(200) NOT NULL,
    `min_price_npr` DECIMAL(10, 2) NOT NULL,
    `max_price_npr` DECIMAL(10, 2) NOT NULL,
    `unit` VARCHAR(50) DEFAULT 'per trip',
    `warning_threshold_npr` DECIMAL(10, 2) DEFAULT NULL,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: organization_broadcasts
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `organization_broadcasts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `target_area` VARCHAR(150) DEFAULT 'All Region',
    `risk_level` ENUM('INFO', 'CAUTION', 'WARNING', 'EMERGENCY') DEFAULT 'CAUTION',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- SEED DATA INITIALIZATION
-- ========================================================

INSERT INTO `organizations` (`id`, `name`, `slug`, `email`, `phone`, `address`, `status`) VALUES
(1, 'Kathmandu Valley Tourism Office', 'kathmandu-tourism', 'contact@ktmtourism.gov.np', '+977-1-4256900', 'Bhrikutimandap, Kathmandu', 'ACTIVE'),
(2, 'Pokhara Tourism Board & Trekking Agency', 'pokhara-tourism', 'info@pokharatourism.org', '+977-61-465247', 'Lakeside, Pokhara', 'ACTIVE');

INSERT INTO `users` (`id`, `organization_id`, `full_name`, `email`, `phone`, `password`, `role`, `emergency_contact_name`, `emergency_contact_phone`) VALUES
(1, NULL, 'Super Administrator', 'superadmin@sahayatri.com', '+977-9800000000', '$2y$10$7R0O1N1S2O3T4U5V6W7X8e9Y0Z1A2B3C4D5E6F7G8H9I0J1K2L3M4', 'super_admin', 'System Support', '+977-9800000000'),
(2, 1, 'Ramesh Adhikari (KTM Admin)', 'ktm_admin@sahayatri.com', '+977-9841234567', '$2y$10$7R0O1N1S2O3T4U5V6W7X8e9Y0Z1A2B3C4D5E6F7G8H9I0J1K2L3M4', 'organization_admin', 'KTM Tourism Desk', '+977-1-4256900'),
(3, 2, 'Sujata Sharma (Pokhara Admin)', 'pokhara_admin@sahayatri.com', '+977-9856012345', '$2y$10$7R0O1N1S2O3T4U5V6W7X8e9Y0Z1A2B3C4D5E6F7G8H9I0J1K2L3M4', 'organization_admin', 'Pokhara Rescue Desk', '+977-61-465247'),
(4, NULL, 'Rohan Shrestha (Traveler)', 'rohan@gmail.com', '+977-9818901234', '$2y$10$7R0O1N1S2O3T4U5V6W7X8e9Y0Z1A2B3C4D5E6F7G8H9I0J1K2L3M4', 'user', 'Hari Shrestha (Father)', '+977-9841112233'),
(5, NULL, 'Aarav Patel (Tourist)', 'aarav@traveler.com', '+91-9876543210', '$2y$10$7R0O1N1S2O3T4U5V6W7X8e9Y0Z1A2B3C4D5E6F7G8H9I0J1K2L3M4', 'user', 'Priya Patel (Sister)', '+91-9876500000');

INSERT INTO `destinations` (`id`, `organization_id`, `name`, `region`, `category`, `description`, `image_url`, `latitude`, `longitude`, `safety_level`, `is_featured`) VALUES
(1, 1, 'Kathmandu Valley Heritage', 'Central Nepal', 'Historical', 'Explore ancient UNESCO World Heritage Durbar Squares, holy stupas, and vibrant culture.', 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=800', 27.7172453, 85.3239605, 'SAFE', 1),
(2, 2, 'Pokhara Lakeside & Annapurna Gateway', 'Western Nepal', 'Natural', 'Serene Phewa Lake surrounded by snow-capped Himalayan peaks, caves, and adventure sports.', 'https://images.unsplash.com/photo-1588668214407-6ea9a6d8c272?w=800', 28.209583, 83.985567, 'SAFE', 1),
(3, 2, 'Everest Base Camp Trek', 'Solukhumbu', 'Trekking', 'World-famous high altitude trek passing Namche Bazaar, Tengboche Monastery to Khumbu Glacier.', 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=800', 27.988120, 86.924975, 'CAUTION', 1),
(4, 1, 'Chitwan National Park Safari', 'Southern Terai', 'Wildlife', 'Dense jungle home to Bengal Tigers, One-horned Rhinos, Crocodiles, and Tharu heritage.', 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=800', 27.529141, 84.444976, 'SAFE', 0);

INSERT INTO `tourist_places` (`id`, `destination_id`, `name`, `category`, `description`, `image_url`, `latitude`, `longitude`, `entry_fee`, `opening_hours`, `rating`) VALUES
(1, 1, 'Swayambhunath Stupa (Monkey Temple)', 'Religious Site', 'Ancient religious architecture perched high on a hill overlooking Kathmandu Valley.', 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=600', 27.7149, 85.2904, 'NPR 200 (Foreigners)', '06:00 AM - 07:00 PM', 4.80),
(2, 1, 'Kathmandu Durbar Square', 'Historical Monument', 'Historic royal palace complex featuring Hanuman Dhoka and living Goddess Kumari Ghar.', 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=600', 27.7042, 85.3071, 'NPR 1000', '08:00 AM - 06:00 PM', 4.65),
(3, 2, 'Phewa Lake & Tal Barahi Temple', 'Natural Lake', 'Boating on Phewa Lake with stunning reflection of Mount Machhapuchhre.', 'https://images.unsplash.com/photo-1588668214407-6ea9a6d8c272?w=600', 28.2083, 83.9575, 'NPR 500 (Boat Ride)', '06:00 AM - 06:00 PM', 4.90),
(4, 2, 'Sarangkot Sunrise Viewpoint', 'Adventure / Sunrise', 'Panoramic sunrise views of Annapurna Range and paragliding take-off hub.', 'https://images.unsplash.com/photo-1588668214407-6ea9a6d8c272?w=600', 28.2439, 83.9486, 'NPR 100', '05:00 AM - 07:00 PM', 4.95);

INSERT INTO `packages` (`id`, `organization_id`, `destination_id`, `title`, `duration_days`, `price_npr`, `inclusions`, `exclusions`, `itinerary_summary`, `max_capacity`, `status`) VALUES
(1, 1, 1, 'Cultural Kathmandu 3-Day Heritage Exploration', 3, 18500.00, 'Hotel, Private Car, Tour Guide, Museum Entry Passes', 'Personal expenses, Alcoholic beverages', 'Day 1: KTM Durbar Square & Swayambhu. Day 2: Patan & Bhaktapur. Day 3: Pashupatinath & Boudha Stupa.', 12, 'ACTIVE'),
(2, 2, 2, 'Pokhara Adventure & Annapurna View Tour', 4, 26000.00, 'Lakeside Hotel, Paragliding, Ultra-light Flight, Boating', 'Travel Insurance, Personal Laundry', 'Day 1: Arrival & Sunset Boating. Day 2: Sarangkot Sunrise & Paragliding. Day 3: Caves & Peace Pagoda.', 15, 'ACTIVE');

INSERT INTO `accommodations` (`id`, `organization_id`, `destination_id`, `name`, `type`, `description`, `address`, `latitude`, `longitude`, `price_per_night`, `rating`, `trust_score`, `is_verified`, `facilities`, `contact_phone`) VALUES
(1, 1, 1, 'Himalayan Heritage Boutique Hotel', 'Hotel', 'Charming hotel combining traditional Nepalese brick architecture with modern luxury in Thamel.', 'Amrit Marg, Thamel, Kathmandu', 27.7154, 85.3123, 6500.00, 4.85, 96, 1, 'WiFi, Hot Water, Breakfast Included, Airport Shuttle, Power Backup', '+977-1-4412345'),
(2, 2, 2, 'Fishtail View Lakeside Resort', 'Resort', 'Peaceful resort right on Phewa Lake edge with garden views of Machhapuchhre.', 'Lakeside Center, Pokhara', 28.2105, 83.9592, 8200.00, 4.90, 98, 1, 'Swimming Pool, Lake View Balcony, Spa, Free WiFi, Organic Restaurant', '+977-61-460111'),
(3, 2, 2, 'Annapurna Peace Homestay', 'Homestay', 'Authentic Gurung hospitality with home-cooked traditional meals and mountain hospitality.', 'Ghandruk Village, Kaski', 28.3752, 83.8073, 2200.00, 4.75, 92, 1, 'Hot Shower, Homemade Meal, Mountain View, Fireplace', '+977-9846099887');

INSERT INTO `teams` (`id`, `team_code`, `name`, `leader_id`, `destination_name`, `start_date`, `end_date`, `status`) VALUES
(1, 'EVEREST-2026', 'Himalayan Explorers Team', 4, 'Pokhara & Annapurna', '2026-08-15', '2026-08-25', 'ACTIVE');

INSERT INTO `team_members` (`id`, `team_id`, `user_id`, `sharing_option`, `status`) VALUES
(1, 1, 4, 'ALWAYS', 'ACTIVE'),
(2, 1, 5, 'ALWAYS', 'ACTIVE');

INSERT INTO `user_locations` (`user_id`, `latitude`, `longitude`, `accuracy`) VALUES
(4, 28.2095, 83.9855, 10.00),
(5, 28.2120, 83.9870, 12.00);

INSERT INTO `safety_zones` (`id`, `organization_id`, `title`, `description`, `latitude`, `longitude`, `radius_meters`, `risk_level`, `risk_type`, `is_active`) VALUES
(1, 2, 'Seti River Gorge High Risk Landslide Zone', 'Active landslide activity reported along Seti gorge after continuous monsoon rain. Avoid walking close to unstable cliffs.', 28.2312, 83.9834, 600, 'CRITICAL', 'Landslide', 1),
(2, 2, 'Sarangkot Downhill Curve Accident Spot', 'Sharp hairpin bends with steep dropdowns. Exercise caution while driving or trekking downhill.', 28.2380, 83.9510, 400, 'HIGH', 'Road accident', 1),
(3, 1, 'Bagmati Riverbank Flood Alert', 'Water level rising due to upper basin rainfall. Riverbank footpaths temporarily closed.', 27.6980, 85.3350, 500, 'CAUTION', 'Flood', 1);

INSERT INTO `emergency_resources` (`id`, `name`, `type`, `phone`, `alternate_phone`, `address`, `city`, `latitude`, `longitude`, `is_verified`) VALUES
(1, 'Nepal Tourist Police Headquarters', 'Tourist Police', '+977-1-4247041', '1144', 'Brikutimandap, Kathmandu', 'Kathmandu', 27.7020, 85.3175, 1),
(2, 'Patan Hospital Emergency Department', 'Hospital', '+977-1-5522295', '+977-1-5522278', 'Lagankhel, Lalitpur', 'Lalitpur', 27.6677, 85.3218, 1),
(3, 'Gandaki Medical College & Hospital', 'Hospital', '+977-61-538595', '+977-61-538596', 'Prithvi Chowk, Pokhara', 'Pokhara', 28.2065, 83.9912, 1),
(4, 'Pokhara Tourist Police Station', 'Tourist Police', '+977-61-462761', '1144', 'Lakeside, Pokhara', 'Pokhara', 28.2090, 83.9580, 1),
(5, 'Himalayan Rescue Association (HRA)', 'Rescue Center', '+977-1-4440292', '+977-1-4440293', 'Dhobighat, Kathmandu', 'Kathmandu', 27.6890, 85.3050, 1);

INSERT INTO `fair_prices` (`id`, `service_category`, `route_or_item`, `min_price_npr`, `max_price_npr`, `unit`, `warning_threshold_npr`, `notes`) VALUES
(1, 'Taxi', 'Tribhuvan Airport (KTM) to Thamel', 700.00, 900.00, 'per trip (prepaid)', 1400.00, 'Standard sedan taxi during day. Night rate (+20%)'),
(2, 'Taxi', 'Pokhara Bus Park to Lakeside', 300.00, 400.00, 'per trip', 700.00, 'Negotiate or demand meter fare.'),
(3, 'Guide', 'Licensed Trekking Guide (Annapurna/Everest)', 3000.00, 4500.00, 'per day', 7000.00, 'Includes guide insurance, food & lodging allowance.'),
(4, 'Local Transport', 'Kathmandu Local Bus (City Route)', 25.00, 40.00, 'per person', 100.00, 'Fare varies by distance (microbus / Sajha Yatayat).'),
(5, 'Food', 'Standard Dal Bhat Set (Local Restaurant)', 350.00, 600.00, 'per meal', 1200.00, 'Includes refills for rice, lentils, and curry in typical restaurants.');

INSERT INTO `reports` (`id`, `user_id`, `category`, `title`, `description`, `location_name`, `latitude`, `longitude`, `service_name`, `status`) VALUES
(1, 4, 'Overcharging', 'Unregistered taxi charging triple rate at Airport', 'Driver demanded NPR 2200 for trip from Tribhuvan Airport to Thamel without using meter.', 'Tribhuvan International Airport', 27.6966, 85.3591, 'Private White Taxi', 'RESOLVED'),
(2, 5, 'Fake Guide', 'Unlicensed guide offering unauthorized trekking permit', 'Individual at Lakeside claiming to provide official TIMS card without registration.', 'Lakeside Street 6, Pokhara', 28.2110, 83.9570, 'Unregistered Trekking Agent', 'UNDER_REVIEW');

INSERT INTO `organization_broadcasts` (`id`, `organization_id`, `title`, `message`, `target_area`, `risk_level`) VALUES
(1, 2, 'MONSOON HIGHWAY CAUTION ALERT', 'Landslide risk near Mugling-Pokhara highway section. Drivers and trekkers are requested to travel during daytime only.', 'Pokhara-Mugling Highway', 'WARNING');
