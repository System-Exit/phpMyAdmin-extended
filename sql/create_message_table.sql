-- ----------------------
--
--
-------------------------

--
-- Creates messages table
--

CREATE TABLE IF NOT EXISTS `pma__messages` (
    `id` integer NOT NULL auto_increment,
    `sender` varchar(255) NOT NULL,
    `receiver` varchar(255) NOT NULL,
    `timestamp` timestamp NOT NULL,
    `message` varchar(255),
    `seen` Boolean NOT NULL,
    PRIMARY KEY  (`id`)
)