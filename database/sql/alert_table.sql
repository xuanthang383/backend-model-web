ALTER TABLE categories ADD CONSTRAINT fk_category_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL;

ALTER TABLE products ADD CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;
ALTER TABLE products ADD CONSTRAINT fk_product_platform FOREIGN KEY (platform_id) REFERENCES platforms(id) ON DELETE SET NULL;
ALTER TABLE products ADD CONSTRAINT fk_product_render FOREIGN KEY (render_id) REFERENCES renders(id) ON DELETE SET NULL;

ALTER TABLE product_colors ADD CONSTRAINT fk_product_color_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;
ALTER TABLE product_colors ADD CONSTRAINT fk_product_color_color FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE;

ALTER TABLE product_materials ADD CONSTRAINT fk_product_material_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;
ALTER TABLE product_materials ADD CONSTRAINT fk_product_material_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE;

ALTER TABLE product_files ADD CONSTRAINT fk_product_file_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;
ALTER TABLE product_files ADD CONSTRAINT fk_product_file_file FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE;

ALTER TABLE product_tags ADD CONSTRAINT fk_product_tag_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;
ALTER TABLE product_tags ADD CONSTRAINT fk_product_tag_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE;
