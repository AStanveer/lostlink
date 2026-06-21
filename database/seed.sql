-- Seed data for local development/testing
USE lostlink;

-- Test users (password: 'password123' for all three)
INSERT INTO users (email, password) VALUES
('alice@utm.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('bob@utm.my',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('carol@utm.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Sample items
INSERT INTO items (title, description, category, location, date, report_type, status, posted_by) VALUES
('Blue Water Bottle', 'Blue Hydro Flask 32oz with stickers on it', 'Accessories', 'Faculty of Computing, Block N28', '2025-05-01 10:00:00', 'lost', 'active', 1),
('Student ID Card', 'UTM student ID card for Alice Tan', 'ID/Card', 'DTC Cafeteria', '2025-05-02 12:30:00', 'lost', 'active', 1),
('Found: Blue Bottle', 'Found a blue water bottle near the lecture hall', 'Accessories', 'N28 Lecture Hall', '2025-05-02 14:00:00', 'found', 'active', 2),
('MacBook Charger', 'USB-C 60W charger, left in tutorial room', 'Electronics', 'Faculty of Computing, Room 3', '2025-05-03 09:00:00', 'lost', 'active', 2);


-- Matches (for testing purposes)
-- Clean up existing items
DELETE FROM items WHERE posted_by IN (2, 3);

-- Add 5 test pairs (10 items total)
INSERT INTO items (title, description, category, location, date, report_type, status, posted_by) VALUES

-- PAIR 1: Easy match - Same item (Should match)
('Blue Water Bottle', '32oz blue Hydro Flask water bottle with stickers', 'Accessories', 'N28 Building', '2025-05-28 10:00:00', 'lost', 'active', 2),
('Blue Water Bottle', 'Found blue Hydro Flask 32oz water bottle with stickers', 'Accessories', 'N28 Building', '2025-05-28 14:00:00', 'found', 'active', 3),

-- PAIR 2: Medium match - Similar item (Should match)
('MacBook Pro', 'Silver MacBook Pro 14-inch laptop with charger', 'Electronics', 'Library', '2025-05-27 09:00:00', 'lost', 'active', 2),
('Laptop Found', 'Silver Apple laptop found in the library', 'Electronics', 'Library', '2025-05-27 11:00:00', 'found', 'active', 3),

-- PAIR 3: Hard match - Only location matches (Should match but low score)
('Phone Charger', 'USB-C charger for iPhone', 'Electronics', 'Cafe', '2025-05-26 15:00:00', 'lost', 'active', 2),
('Power Bank', 'Portable charger with USB cable', 'Accessories', 'Cafe', '2025-05-26 16:00:00', 'found', 'active', 3),

-- PAIR 4: No match - Different items (Should NOT match)
('Calculus Textbook', 'Calculus textbook by Stewart', 'Books', 'Bookstore', '2025-05-25 12:00:00', 'lost', 'active', 2),
('Black Umbrella', 'Black collapsible umbrella', 'Accessories', 'Main Gate', '2025-05-25 13:00:00', 'found', 'active', 3),

-- PAIR 5: No match - Different everything (Should NOT match)
('Car Keys', 'Car keys with leather keychain', 'Keys', 'Parking Lot', '2025-05-24 08:00:00', 'lost', 'active', 2),
('Leather Jacket', 'Black leather jacket size M', 'Clothing', 'Gymnasium', '2025-05-24 09:00:00', 'found', 'active', 3);