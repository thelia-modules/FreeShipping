
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- free_shipping
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `free_shipping`;

CREATE TABLE `free_shipping`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `area_id` INTEGER NOT NULL,
    `amount` INTEGER NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `FI_area_associated_freeShipping_area_id` (`area_id`),
    CONSTRAINT `fk_area_associated_freeShipping_area_id`
        FOREIGN KEY (`area_id`)
        REFERENCES `area` (`id`)
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
