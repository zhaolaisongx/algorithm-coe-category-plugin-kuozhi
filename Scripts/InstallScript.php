<?php

include_once __DIR__.'/BaseInstallScript.php';

class InstallScript extends BaseInstallScript
{
    public function install()
    {
        $connection = $this->getConnection();
        /* create you database table 还需插入category_group 一条数据，再默认插入一条数据才能启动 应该用command*/
        $connection->exec("
            CREATE TABLE IF NOT EXISTS `category_test` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '分类ID',
              `code` varchar(64) NOT NULL DEFAULT '' COMMENT '分类编码',
              `name` varchar(255) NOT NULL COMMENT '分类名称',
              `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '图标',
              `path` varchar(255) NOT NULL DEFAULT '' COMMENT '分类完整路径',
              `weight` int(11) NOT NULL DEFAULT '0' COMMENT '分类权重',
              `groupId` int(10) unsigned NOT NULL COMMENT '分类组ID',
              `parentId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父分类ID',
              `orgId` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '组织机构ID',
              `orgCode` varchar(255) NOT NULL DEFAULT '1.' COMMENT '组织机构内部编码',
              `description` text,
              `leftNum` int(11) NOT NULL DEFAULT '0' COMMENT '左边点',
              `rightNum` int(11) NOT NULL DEFAULT '0' COMMENT '右边点',
              PRIMARY KEY (`id`),
              UNIQUE KEY `uri` (`code`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
        ");
    }
}
