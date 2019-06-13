<?php

namespace CategoryTestPlugin\Biz\CategoryTest\Dao;

interface CategoryTestDao
{
    public function getByCode($code);

    public function findByGroupIdAndParentId($groupId, $parentId);

    /**
     * @deprecated  即将废弃不建议使用
     *
     * @param $parentId
     * @param $orderBy
     * @param $start
     * @param $limit
     *
     * @return mixed
     */
    public function findByParentId($parentId, $orderBy, $start, $limit);

    public function findAllByParentId($parentId);

    public function findCountByParentId($parentId);

    public function findByGroupId($groupId);

    public function findByGroupIdAndOrgId($groupId, $orgId);

    public function findByIds(array $ids);

    public function findAll();
}