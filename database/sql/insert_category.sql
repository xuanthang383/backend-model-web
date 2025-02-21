INSERT INTO categories (name, parent_id) VALUES 
('Chair', NULL),
('Table', NULL),
('Sofa', NULL),
('Lighting', NULL),
('Cabinet', NULL),
('Decorate', NULL),
('Plant', NULL),
('Kitchen', NULL),
('Bedroom', NULL),
('Bathroom', NULL);

-- Insert các category con cho từng category cha
INSERT INTO categories (name, parent_id) VALUES 
-- Chair subcategories
('Armchair', (SELECT id FROM categories WHERE name = 'Chair')),
('Office Chair', (SELECT id FROM categories WHERE name = 'Chair')),
('Children’s Chair', (SELECT id FROM categories WHERE name = 'Chair')),
('Bar Stool', (SELECT id FROM categories WHERE name = 'Chair')),

-- Table subcategories
('Desk', (SELECT id FROM categories WHERE name = 'Table')),
('Tea Table', (SELECT id FROM categories WHERE name = 'Table')),
('Dressing Table', (SELECT id FROM categories WHERE name = 'Table')),

-- Sofa subcategories
('Single Sofa', (SELECT id FROM categories WHERE name = 'Sofa')),
('Multi-person Sofa', (SELECT id FROM categories WHERE name = 'Sofa')),
('Bean Bag', (SELECT id FROM categories WHERE name = 'Sofa')),
('Special Shaped Sofa', (SELECT id FROM categories WHERE name = 'Sofa')),

-- Lighting subcategories
('Floor Lamp', (SELECT id FROM categories WHERE name = 'Lighting')),
('Ceiling Light', (SELECT id FROM categories WHERE name = 'Lighting')),
('Table Lamp', (SELECT id FROM categories WHERE name = 'Lighting')),
('Wall Lamp', (SELECT id FROM categories WHERE name = 'Lighting')),
('Chandelier', (SELECT id FROM categories WHERE name = 'Lighting')),
('Decorative Light', (SELECT id FROM categories WHERE name = 'Lighting')),
('Spot Light', (SELECT id FROM categories WHERE name = 'Lighting')),

-- Cabinet subcategories
('TV Cabinet', (SELECT id FROM categories WHERE name = 'Cabinet')),
('Side Board', (SELECT id FROM categories WHERE name = 'Cabinet')),
('Book Case', (SELECT id FROM categories WHERE name = 'Cabinet')),
('Wardrobe', (SELECT id FROM categories WHERE name = 'Cabinet')),
('Shoe Cabinet', (SELECT id FROM categories WHERE name = 'Cabinet')),

-- Decorate subcategories
('Sculpture', (SELECT id FROM categories WHERE name = 'Decorate')),
('Book', (SELECT id FROM categories WHERE name = 'Decorate')),
('Mirror', (SELECT id FROM categories WHERE name = 'Decorate')),
('Vase', (SELECT id FROM categories WHERE name = 'Decorate')),
('Frame', (SELECT id FROM categories WHERE name = 'Decorate')),
('Curtain', (SELECT id FROM categories WHERE name = 'Decorate')),
('Hardware', (SELECT id FROM categories WHERE name = 'Decorate')),
('Carpet', (SELECT id FROM categories WHERE name = 'Decorate')),
('Door', (SELECT id FROM categories WHERE name = 'Decorate')),
('Other', (SELECT id FROM categories WHERE name = 'Decorate'));
