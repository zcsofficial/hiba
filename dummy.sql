-- Categories (Hierarchical: 5 main + 5 subcategories)
INSERT INTO categories (parent_id, name, description) VALUES
(NULL, 'Electronics', 'Electronic gadgets and devices'),
(NULL, 'Clothing', 'Fashion and apparel'),
(NULL, 'Home & Garden', 'Home decor and gardening'),
(NULL, 'Books', 'Literature and educational books'),
(NULL, 'Sports', 'Sports equipment and accessories'),
(1, 'Smartphones', 'Mobile phones and accessories'),
(2, 'Men''s Fashion', 'Men''s clothing and accessories'),
(3, 'Kitchen', 'Kitchen appliances and utensils'),
(4, 'Fiction', 'Fictional books and novels'),
(5, 'Fitness', 'Fitness equipment');

-- Products (10 products across categories)
INSERT INTO products (category_id, name, description, price, original_price, amazon_affiliate_link, main_image_url, rating, review_count, is_featured) VALUES
(6, 'Smartphone X1', 'Latest 5G smartphone', 599.99, 649.99, 'https://amazon.com/dp/B08XYZ1', 'https://images.unsplash.com/photo-1511707171634-5', 4.5, 120, TRUE),
(7, 'Leather Jacket', 'Premium leather jacket', 129.99, 149.99, 'https://amazon.com/dp/B08XYZ2', 'https://images.unsplash.com/photo-1551488831-8', 4.2, 85, FALSE),
(8, 'Blender Pro', 'High-power kitchen blender', 89.99, NULL, 'https://amazon.com/dp/B08XYZ3', 'https://images.unsplash.com/photo-1584735175-9', 4.0, 45, TRUE),
(9, 'Mystery Novel', 'Bestselling fiction book', 14.99, 19.99, 'https://amazon.com/dp/B08XYZ4', 'https://images.unsplash.com/photo-1544947950-0', 4.8, 200, FALSE),
(10, 'Yoga Mat', 'Premium fitness mat', 29.99, NULL, 'https://amazon.com/dp/B08XYZ5', 'https://images.unsplash.com/photo-1576678927-1', 4.3, 65, TRUE),
(6, 'Wireless Earbuds', 'Noise-cancelling earbuds', 79.99, 99.99, 'https://amazon.com/dp/B08XYZ6', 'https://images.unsplash.com/photo-1590658268-2', 4.4, 90, FALSE),
(7, 'Cotton T-Shirt', 'Comfortable casual shirt', 19.99, NULL, 'https://amazon.com/dp/B08XYZ7', 'https://images.unsplash.com/photo-1521572163-3', 4.1, 150, FALSE),
(8, 'Cookware Set', '10-piece kitchen set', 149.99, 179.99, 'https://amazon.com/dp/B08XYZ8', 'https://images.unsplash.com/photo-1556911220-4', 4.6, 75, TRUE),
(9, 'Sci-Fi Book', 'Space adventure novel', 12.99, NULL, 'https://amazon.com/dp/B08XYZ9', 'https://images.unsplash.com/photo-1519681393-5', 4.7, 110, FALSE),
(10, 'Dumbbells', 'Adjustable weight set', 59.99, 69.99, 'https://amazon.com/dp/B08XYZ0', 'https://images.unsplash.com/photo-1585556698-6', 4.5, 95, TRUE);

-- Product_images
INSERT INTO product_images (product_id, image_url, is_thumbnail) VALUES
(1, 'https://images.unsplash.com/photo-1511707171634-5', TRUE),
(1, 'https://images.unsplash.com/photo-1511707171634-6', FALSE),
(2, 'https://images.unsplash.com/photo-1551488831-8', TRUE),
(3, 'https://images.unsplash.com/photo-1584735175-9', TRUE),
(4, 'https://images.unsplash.com/photo-1544947950-0', TRUE),
(5, 'https://images.unsplash.com/photo-1576678927-1', TRUE),
(6, 'https://images.unsplash.com/photo-1590658268-2', TRUE),
(7, 'https://images.unsplash.com/photo-1521572163-3', TRUE),
(8, 'https://images.unsplash.com/photo-1556911220-4', TRUE),
(9, 'https://images.unsplash.com/photo-1519681393-5', TRUE);

-- Product_colors
INSERT INTO product_colors (product_id, color_name) VALUES
(1, 'Space Gray'),
(1, 'Silver'),
(2, 'Black'),
(2, 'Brown'),
(3, 'Stainless Steel'),
(5, 'Blue'),
(6, 'White'),
(7, 'Gray'),
(8, 'Red'),
(10, 'Black');

-- Reviews
INSERT INTO reviews (product_id, user_id, reviewer_name, rating, comment, is_verified) VALUES
(1, NULL, 'John Doe', 4.5, 'Great phone!', TRUE),
(2, NULL, 'Jane Smith', 4.0, 'Nice jacket', FALSE),
(3, NULL, 'Mike Johnson', 3.5, 'Good blender', TRUE),
(4, NULL, 'Sarah Williams', 5.0, 'Amazing book!', TRUE),
(5, NULL, 'Tom Brown', 4.0, 'Comfy mat', FALSE),
(6, NULL, 'Emily Davis', 4.5, 'Clear sound', TRUE),
(7, NULL, 'Chris Wilson', 3.5, 'Basic shirt', FALSE),
(8, NULL, 'Lisa Anderson', 4.5, 'Love this set', TRUE),
(9, NULL, 'Robert Taylor', 5.0, 'Great story', TRUE),
(10, NULL, 'Kelly Martin', 4.0, 'Solid weights', FALSE);

-- Special_offers
INSERT INTO special_offers (title, description, image_url, discount_percentage, start_time, end_time) VALUES
('Spring Sale', 'Spring discounts', 'https://images.unsplash.com/photo-1618221195-1', 20.00, '2025-03-01 00:00:00', '2025-03-15 23:59:59'),
('Tech Deals', 'Electronics sale', 'https://images.unsplash.com/photo-1518770660-2', 15.00, '2025-04-01 00:00:00', '2025-04-07 23:59:59'),
('Fashion Week', 'Clothing offers', 'https://images.unsplash.com/photo-1483985988-3', 25.00, '2025-05-01 00:00:00', '2025-05-10 23:59:59'),
('Home Special', 'Home items sale', 'https://images.unsplash.com/photo-1588854337-4', 30.00, '2025-06-01 00:00:00', '2025-06-15 23:59:59'),
('Book Bonanza', 'Book discounts', 'https://images.unsplash.com/photo-1519681393-5', 10.00, '2025-07-01 00:00:00', '2025-07-07 23:59:59'),
('Fitness Frenzy', 'Sports deals', 'https://images.unsplash.com/photo-1576678927-6', 20.00, '2025-08-01 00:00:00', '2025-08-15 23:59:59'),
('Summer Sale', 'Summer specials', 'https://images.unsplash.com/photo-1563013544-7', 25.00, '2025-06-20 00:00:00', '2025-07-20 23:59:59'),
('Tech Blast', 'Gadget deals', 'https://images.unsplash.com/photo-1511707171-8', 15.00, '2025-09-01 00:00:00', '2025-09-10 23:59:59'),
('Fall Fashion', 'Fall clothing', 'https://images.unsplash.com/photo-1532453288-9', 20.00, '2025-10-01 00:00:00', '2025-10-15 23:59:59'),
('Winter Prep', 'Winter essentials', 'https://images.unsplash.com/photo-1542601906-0', 30.00, '2025-11-01 00:00:00', '2025-11-20 23:59:59');

-- Offer_products
INSERT INTO offer_products (offer_id, product_id) VALUES
(1, 1),
(1, 6),
(2, 1),
(2, 6),
(3, 2),
(3, 7),
(4, 3),
(4, 8),
(5, 4),
(5, 9);

-- Blog_posts
INSERT INTO blog_posts (title, description, image_url, content) VALUES
('Tech Trends 2025', 'Latest tech', 'https://images.unsplash.com/photo-1518770660-0', 'Content about tech...'),
('Fashion Tips', 'Style guide', 'https://images.unsplash.com/photo-1483985988-1', 'Fashion advice...'),
('Kitchen Hacks', 'Cooking tips', 'https://images.unsplash.com/photo-1588854337-2', 'Kitchen tricks...'),
('Book Reviews', 'Top books', 'https://images.unsplash.com/photo-1519681393-3', 'Book reviews...'),
('Fitness Guide', 'Workout tips', 'https://images.unsplash.com/photo-1576678927-4', 'Fitness advice...'),
('Gadget Review', 'Tech review', 'https://images.unsplash.com/photo-1511707171-5', 'Gadget details...'),
('Style Trends', 'Fashion news', 'https://images.unsplash.com/photo-1532453288-6', 'Style updates...'),
('Cooking 101', 'Basic recipes', 'https://images.unsplash.com/photo-1556911220-7', 'Cooking basics...'),
('Reading List', 'Book picks', 'https://images.unsplash.com/photo-1544947950-8', 'Book suggestions...'),
('Sports Tips', 'Training guide', 'https://images.unsplash.com/photo-1585556698-9', 'Sports advice...');

-- Newsletter_subscribers
INSERT INTO newsletter_subscribers (email) VALUES
('john@example.com'),
('jane@example.com'),
('mike@example.com'),
('sarah@example.com'),
('tom@example.com'),
('emily@example.com'),
('chris@example.com'),
('lisa@example.com'),
('robert@example.com'),
('kelly@example.com');

-- Related_products
INSERT INTO related_products (product_id, related_product_id) VALUES
(1, 6),
(2, 7),
(3, 8),
(4, 9),
(5, 10),
(6, 1),
(7, 2),
(8, 3),
(9, 4),
(10, 5);