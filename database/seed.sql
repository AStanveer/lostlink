-- Seed data for local development/testing
USE lostlink;

-- Test users (password: 'password123' for both)
INSERT INTO users (email, password) VALUES
('alice@utm.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('bob@utm.my',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Sample items
INSERT INTO items (title, description, category, location, date, report_type, status, posted_by) VALUES
('Blue Water Bottle', 'Blue Hydro Flask 32oz with stickers on it', 'Accessories', 'Faculty of Computing, Block N28', '2025-05-01 10:00:00', 'lost', 'active', 1),
('Student ID Card', 'UTM student ID card for Alice Tan', 'ID/Card', 'DTC Cafeteria', '2025-05-02 12:30:00', 'lost', 'active', 1),
('Found: Blue Bottle', 'Found a blue water bottle near the lecture hall', 'Accessories', 'N28 Lecture Hall', '2025-05-02 14:00:00', 'found', 'active', 2),
('MacBook Charger', 'USB-C 60W charger, left in tutorial room', 'Electronics', 'Faculty of Computing, Room 3', '2025-05-03 09:00:00', 'lost', 'active', 2);
