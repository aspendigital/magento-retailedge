<?php

$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$installer->getTable('retailedge/category_map')};
CREATE TABLE {$installer->getTable('retailedge/category_map')} (
  `retailedge_list_id` bigint(14) unsigned ZEROFILL NOT NULL AUTO_INCREMENT,
  `department_name` varchar(255) NOT NULL default '',
  `category_id` int(11) unsigned NOT NULL,
  `category_name` text NOT NULL default '',
  PRIMARY KEY (`retailedge_list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");

$installer->run("
DROP TABLE IF EXISTS {$installer->getTable('retailedge/cron_info')};
CREATE TABLE {$installer->getTable('retailedge/cron_info')} (
  `last_run` DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");


$type_id = $installer->getEntityTypeId('catalog_product');

$attr = array(
		'type'=>'varchar',
		'label'=>'RetailEdge Product List ID',
		'user_defined'=>0,
		'required'=>0,
		'unique'=>1,
		'input'=>''
	);
$installer->addAttribute($type_id, 'retailedge_list_id', $attr);


$installer->endSetup();
?>
