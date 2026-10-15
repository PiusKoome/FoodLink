INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `phone`, `location`, `created_at`) VALUES (3, 'Surbub Hotel', 'surbub@gmail.com', '$2y$10$MsVOtqTpXgs0QngX6baPoONAFev4/kx3I5SC5HhROmFEwBAeuk9AK', 'restaurant_owner', '0797035917', 'Nanyuki', '2026-07-13 10:46:16');
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `phone`, `location`, `created_at`) VALUES (2, 'pius', 'pius@gmail.com', '$2y$10$f61Z1tUxR0kAW6x6E.sMVurWPmdJeW3BnAU1XUaiDCXmBnbthwoGi', 'customer', '0797035917', 'Nairobi', '2026-07-13 10:37:52');

INSERT INTO `restaurants` (`id`, `owner_id`, `name`, `location`, `description`, `status`, `created_at`) VALUES (1, 3, 'Surbub Hotel', 'Nanyuki', 'Fast and Reliable', 'approved', '2026-07-13 10:47:50');

INSERT INTO `menu_items` (`id`, `restaurant_id`, `name`, `category`, `price`, `description`, `availability`, `image_path`, `created_at`) VALUES (1, 1, 'Chicken Biryani', 'Rice Dishes', '1500.00', 'chicken served with pilau', 'Available', 'uploads/menu/1783928936_Savoring every flavorful grain in a plate of biryani bliss_Chiken_Chiken biriyani_Rice_forizen food.jpg', '2026-07-13 10:48:56');
INSERT INTO `menu_items` (`id`, `restaurant_id`, `name`, `category`, `price`, `description`, `availability`, `image_path`, `created_at`) VALUES (3, 1, 'mains', 'Ugali Mayai', '700.00', 'ugali served served with mayai', 'Available', 'uploads/menu/1787056768_Ugali with Scrambled Eggs & Sautéed Greens – Simple Kenyan Meal.jpg', '2026-08-18 15:39:28');

INSERT INTO `meal_schedules` (`id`, `customer_id`, `restaurant_id`, `menu_item_id`, `meal_type`, `schedule_type`, `schedule_date`, `quantity`, `created_at`) VALUES (1, 2, 1, 1, 'breakfast', 'weekly', 'monday', 1, '2026-07-13 10:51:46');

INSERT INTO `orders` (`id`, `customer_id`, `restaurant_id`, `delivery_address`, `total_amount`, `status`, `payment_status`, `delivery_person`, `restaurant_order_number`, `created_at`) VALUES (1, 2, 1, 'Nairobi', '1500.00', 'delivering', 'pending', '', 1, '2026-07-13 10:52:17');
INSERT INTO `orders` (`id`, `customer_id`, `restaurant_id`, `delivery_address`, `total_amount`, `status`, `payment_status`, `delivery_person`, `restaurant_order_number`, `created_at`) VALUES (2, 2, 1, 'Nairobi', '1500.00', 'confirmed', 'pending', '', 2, '2026-07-16 15:31:48');

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `subtotal`) VALUES (1, 1, 1, 1, '1500.00', '1500.00');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `unit_price`, `subtotal`) VALUES (2, 2, 1, 1, '1500.00', '1500.00');

INSERT INTO `payments` (`id`, `order_id`, `amount`, `method`, `transaction_code`, `status`, `created_at`) VALUES (1, 1, '1500.00', 'M-Pesa', 'TXN-1', 'paid', '2026-07-13 10:52:17');
INSERT INTO `payments` (`id`, `order_id`, `amount`, `method`, `transaction_code`, `status`, `created_at`) VALUES (2, 2, '1500.00', 'M-Pesa', 'TXN-2', 'paid', '2026-07-16 15:31:48');

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES (1, 3, 'New order received', 'pius placed a new order for Chicken Biryani.', 'update_order.php?id=2', 1, '2026-07-16 15:31:48');

