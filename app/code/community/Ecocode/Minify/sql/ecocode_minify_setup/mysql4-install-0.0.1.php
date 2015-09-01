<?php
$installer = $this;

$installer->startSetup();
$installer->run("
    CREATE  TABLE`{$this->getTable('ecocode_minify/minify_log')}` (
      `id` INT(11) NOT NULL AUTO_INCREMENT ,
      `timestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ,
      `type` VARCHAR(50) NULL ,
      `message` VARCHAR(255) NULL ,
      `details` LONGTEXT NULL ,
     PRIMARY KEY (`id`) )
    ENGINE = InnoDB
    DEFAULT CHARACTER SET = utf8;
");
$installer->endSetup();