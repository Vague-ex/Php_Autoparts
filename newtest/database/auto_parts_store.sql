-- Inserts new items into the existing auto_parts_store.items table.
-- Run this against the auto_parts_store database (no CREATE DATABASE / USE statements here).

INSERT INTO items (name, type, price, image_url) VALUES
('Brake Pads', 'Braking System', 45.99, 'https://placehold.co/200x200?text=Brake+Pads'),
('Oil Filter', 'Maintenance', 12.99, 'https://placehold.co/200x200?text=Oil+Filter'),
('Spark Plugs', 'Engine', 8.99, 'https://placehold.co/200x200?text=Spark+Plugs'),
('Air Filter', 'Engine', 15.99, 'https://placehold.co/200x200?text=Air+Filter'),
('Wiper Blades', 'Exterior', 22.99, 'https://placehold.co/200x200?text=Wiper+Blades'),
('Battery', 'Electrical', 129.99, 'https://placehold.co/200x200?text=Battery'),
('Headlight Bulb', 'Lighting', 19.99, 'https://placehold.co/200x200?text=Headlight'),
('Tire', 'Wheels', 89.99, 'https://placehold.co/200x200?text=Tire');

