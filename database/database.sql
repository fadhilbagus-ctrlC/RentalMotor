DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin','user') NOT NULL DEFAULT 'user',
    `profile_photo` VARCHAR(255) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Users (password: bcrypt hash, password asli: '123' untuk keduanya)
-- Ganti hash ini di production dan jangan commit password asli.
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$10$BHL9ImTcXcK29L.yocMVV.BQM195d61ujMMMVQ3iHpmCnPex33mDW', 'admin'),
(2, 'User', 'user@gmail.com', '$2y$10$BHL9ImTcXcK29L.yocMVV.BQM195d61ujMMMVQ3iHpmCnPex33mDW', 'user');