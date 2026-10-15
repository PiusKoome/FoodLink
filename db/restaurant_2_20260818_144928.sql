INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `phone`, `location`, `created_at`) VALUES (4, 'Nickolee', 'nickolee@gmail.com', '$2y$10$2w20YhJbIuVYh9BGZfbI9uhRpH9.3GV0K9WfnL.0oo3UcTzou/Xuy', 'restaurant_owner', '0797035917', 'Nairobi', '2026-07-13 11:26:20');

INSERT INTO `restaurants` (`id`, `owner_id`, `name`, `location`, `description`, `status`, `created_at`) VALUES (2, 4, 'Nickolee Hotel', 'Nanyuki', 'Fast Meal', 'approved', '2026-07-13 11:28:36');

INSERT INTO `menu_items` (`id`, `restaurant_id`, `name`, `category`, `price`, `description`, `availability`, `image_path`, `created_at`) VALUES (2, 2, 'Chicken Biryani', 'Rice Dishes', '1200.00', 'chicken with pilau', 'Available', 'uploads/menu/1783931413_Savoring every flavorful grain in a plate of biryani bliss_Chiken_Chiken biriyani_Rice_forizen food.jpg', '2026-07-13 11:30:13');

-- No rows found for meal_schedules

-- No rows found for orders

-- No rows found for order_items

-- No rows found for payments

-- No rows found for notifications

