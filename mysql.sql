CREATE TABLE IF NOT EXISTS `devices` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `mac` varchar(30) NOT NULL UNIQUE,
    `filter_id` varchar(10) NOT NULL,
    `device_name` varchar(255) NOT NULL,
    `access_type` enum('full', 'limited', 'blocked') NOT NULL,
    `allow_extended_access` tinyint(1) NOT NULL default 0,
    `block_full_access_request_until` datetime default CURRENT_TIMESTAMP NOT NULL,
    `full_access_used_today` tinyint(1) NOT NULL default 0,
    `created_at` datetime default CURRENT_TIMESTAMP NOT NULL,
    `updated_at` datetime default CURRENT_TIMESTAMP NOT NULL
);
CREATE TABLE IF NOT EXISTS `access_queue` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `device_id` int(11) NOT NULL,
    `next_access_type` enum('full', 'limited', 'blocked') NOT NULL,
    `next_access_at` datetime NOT NULL,
    `status` enum('pending', 'completed', 'cancelled') default 'pending' NOT NULL,
    `comment` varchar(255) default '',
    `created_at` datetime default CURRENT_TIMESTAMP NOT NULL,
    `updated_at` datetime default CURRENT_TIMESTAMP NOT NULL,
    foreign key (device_id) references devices(id)
);