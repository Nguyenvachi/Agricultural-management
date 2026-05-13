-- =========================================================
-- ADMIN SEED USER
-- Run sau khi đã import Agricultural.sql
-- Username: admin / Password: admin123
-- =========================================================
-- role_id của ADMIN = id của sys_lookup_values WHERE code='ADMIN' AND type_id=1
-- Dùng subquery để tự động lấy đúng ID

INSERT INTO `users`
    (`role_id`, `agency_id`, `username`, `password_hash`, `full_name`, `phone`, `is_active`)
VALUES (
    (SELECT id FROM sys_lookup_values WHERE code = 'ADMIN' AND type_id = (SELECT id FROM sys_lookup_types WHERE code = 'USER_ROLE') LIMIT 1),
    NULL,
    'admin',
    '$2y$10$FHzGyhiK1d/HUt/EPLLzLOjnH4BcrF1UEqA/b0moHDqpkmKIn3rsW',
    'Quản trị viên',
    NULL,
    1
);
