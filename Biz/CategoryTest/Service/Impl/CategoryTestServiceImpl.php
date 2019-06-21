<?php
namespace CategoryTestPlugin\Biz\CategoryTest\Service\Impl;

/**
 * EduSoho系统可引用以下BaseService
 * Biz\BaseService
 */

use Biz\BaseService;
use Biz\Common\CommonException;
use Biz\System\Service\LogService;
use Biz\System\Service\SettingService;
use Biz\Taxonomy\CategoryException;
use Biz\Taxonomy\Dao\CategoryGroupDao;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\TreeToolkit;
use CategoryTestPlugin\Biz\CategoryTest\Dao\CategoryTestDao;
use CategoryTestPlugin\Biz\CategoryTest\Service\CategoryTestService;

class CategoryTestServiceImpl extends BaseService implements CategoryTestService
{

    public function findCategoriesByGroupIdAndParentId($groupId, $parentId)
    {
        if (!isset($groupId) || !isset($parentId) || $groupId < 0 || $parentId < 0) {
            return array();
        }

        return $this->getCategoryTestDao()->findByGroupIdAndParentId($groupId, $parentId);
    }

    public function getCategory($id)
    {
        if (empty($id)) {
            return null;
        }

        return $this->getCategoryTestDao()->get($id);
    }

    public function getCategoryByCode($code)
    {
        return $this->getCategoryTestDao()->getByCode($code);
    }

    /*ForCustomOnly*/
    /*好处：
    1. 所有数据筛选出来以后，数据处理简单化  少了一堆方法
    2. 单个节点数据筛选方便
    3.
    */
    private function prepareTreeData($categories)
    {
        $position = array();
        $depthArr = array();
        foreach ($categories as &$category) {
            if (empty($position)) {
                $position[] = $category['rightNum'];
                $depth = 1;
                $depthArr[$category['rightNum']] = $depth;
                $category['depth'] = $depth;
                continue;
            }
            $tryData = array_reverse($position);
            foreach ($tryData as $key => $tryDatum) {
                if ($category['rightNum'] <= $tryDatum) {
                    $position[] = $category['rightNum'];
                    $depth = $depthArr[$tryDatum] + 1;
                    $depthArr[$category['rightNum']] = $depth;
                    $category['depth'] = $depth;
                    break;
                }
                if ($position[0] == $tryDatum) {
                    $position[] = $category['rightNum'];
                    $depth = 1;
                    $depthArr[$category['rightNum']] = $depth;
                    $category['depth'] = $depth;
                    break;
                }
            }
        }

        return $categories;
    }
    /*END*/

    public function getCategoryTree($groupId)
    {
        $group = $this->getGroup($groupId);

        if (empty($group)) {
            $this->createNewException(CategoryException::NOTFOUND_GROUP());
        }

        /*$prepare = function ($categories) {
            $prepared = array();

            foreach ($categories as $category) {
                if (!isset($prepared[$category['parentId']])) {
                    $prepared[$category['parentId']] = array();
                }

                $prepared[$category['parentId']][] = $category;
            }

            return $prepared;
        };
        $data = $this->findCategories($groupId);
        $categories = $prepare($data);

        $tree = array();
        $this->makeCategoryTree($tree, $categories, 0);*/

        $data = $this->findCategoriesOrderByLeftNumASC($groupId);
        $tree = $this->prepareTreeData($data);

        return $tree;
    }

    public function getCategoryStructureTree($groupId)
    {
        return $this->getCategoryTree($groupId);
        /*return TreeToolkit::makeTree($this->getCategoryTree($groupId), 'weight');*/
    }

    public function sortCategories($ids)
    {
        foreach ($ids as $index => $id) {
            $this->updateCategory($id, array('weight' => $index + 1));
        }
    }

    public function findCategories($groupId)
    {
        $group = $this->getGroup($groupId);

        if (empty($group)) {
            $this->createNewException(CategoryException::NOTFOUND_GROUP());
        }

        $magic = $this->getSettingService()->get('magic');

        if (isset($magic['enable_org']) && $magic['enable_org']) {
            $user = $this->getCurrentUser();
            $orgId = !empty($user['org']) ? $user['org']['id'] : null;

            return $this->getCategoryTestDao()->findByGroupIdAndOrgId($group['id'], $orgId);
        } else {
            return $this->getCategoryTestDao()->findByGroupId($group['id']);
        }
    }

    public function findCategoriesOrderByLeftNumASC($groupId)
    {
        $group = $this->getGroup($groupId);

        if (empty($group)) {
            $this->createNewException(CategoryException::NOTFOUND_GROUP());
        }

        return $this->getCategoryTestDao()->findByGroupIdOrderByLeftNumASC($group['id']);
    }

    public function findAllCategoriesByParentId($parentId)
    {
        return ArrayToolkit::index($this->getCategoryTestDao()->findAllByParentId($parentId), 'id');
    }

    public function findGroupRootCategories($groupCode)
    {
        $group = $this->getGroupByCode($groupCode);

        if (empty($group)) {
            $this->createNewException(CategoryException::NOTFOUND_GROUP());
        }

        return $this->getCategoryTestDao()->findByGroupIdAndParentId($group['id'], 0);
    }

    public function findCategoryChildrenIds($id)
    {
        $category = $this->getCategory($id);

        if (empty($category)) {
            return array();
        }

        $tree = $this->getCategoryTree($category['groupId']);

        $childrenIds = array();
        $depth = 0;

        foreach ($tree as $node) {
            if ($node['id'] == $category['id']) {
                $depth = $node['depth'];
                continue;
            }

            if ($depth > 0 && $depth < $node['depth']) {
                $childrenIds[] = $node['id'];
            }

            if ($depth > 0 && $depth >= $node['depth']) {
                break;
            }
        }

        return $childrenIds;
    }

    public function findCategoryBreadcrumbs($categoryId)
    {
        $breadcrumbs = array();
        $category = $this->getCategory($categoryId);

        if (empty($category)) {
            return array();
        }

        $categoryTree = $this->getCategoryTree($category['groupId']);

        $indexedCategories = ArrayToolkit::index($categoryTree, 'id');

        while (true) {
            if (empty($indexedCategories[$categoryId])) {
                break;
            }

            $category = $indexedCategories[$categoryId];
            $breadcrumbs[] = $category;

            if (empty($category['parentId'])) {
                break;
            }

            $categoryId = $category['parentId'];
        }

        return array_reverse($breadcrumbs);
    }

    public function makeNavCategories($code, $groupCode)
    {
        $rootCagoies = $this->findGroupRootCategories($groupCode);

        if (empty($code)) {
            return array($rootCagoies, array(), array());
        } else {
            $category = $this->getCategoryByCode($code);
            $parentId = $category['id'];
            $categories = array();
            $activeIds = array();
            $activeIds[] = $category['id'];
            $level = 1;

            while ($parentId) {
                $activeIds[] = $parentId;
                $sibling = $this->findAllCategoriesByParentId($parentId);

                if ($sibling) {
                    $categories[$level] = $sibling;
                    ++$level;
                }

                $parent = $this->getCategory($parentId);
                $parentId = $parent['parentId'];
            }

            //翻转会重建key索引
            $categories = array_reverse($categories);

            return array($rootCagoies, $categories, $activeIds);
        }
    }

    public function findCategoriesByIds(array $ids)
    {
        return ArrayToolkit::index($this->getCategoryTestDao()->findByIds($ids), 'id');
    }

    public function findAllCategories()
    {
        return $this->getCategoryTestDao()->findAll();
    }

    public function isCategoryCodeAvailable($code, $exclude = null)
    {
        if (empty($code)) {
            return false;
        }

        if ($code == $exclude) {
            return true;
        }

        $category = $this->getCategoryTestDao()->getByCode($code);

        return $category ? false : true;
    }

    public function createCategory(array $category)
    {
        $category = ArrayToolkit::parts($category, array('description', 'name', 'code', 'groupId', 'parentId', 'icon'));

        if (!ArrayToolkit::requireds($category, array('name', 'code', 'groupId', 'parentId'))) {
            $this->createNewException(CommonException::ERROR_PARAMETER_MISSING());
        }

        $this->filterCategoryFields($category);

        $category = $this->setCategoryOrg($category);
        $category = $this->getCategoryTestDao()->create($category);
        /*ForCustomOnly*/
        if ($category['parentId'] < 1) {
            $lastCategory = $this->getLastCategory();
            $pointNum['leftNum'] = (empty($lastCategory['rightNum']) ? 0 : $lastCategory['rightNum']) + 1;
            $pointNum['rightNum'] = $pointNum['leftNum'] + 1;
            $this->updateCategory($category['id'], $pointNum);
        } else {
            $parentCategory = $this->getCategory($category['parentId']);
            $parentPointNum['rightNum'] =  $parentCategory['rightNum'] + 2;
            $currentPointNum['leftNum'] = $parentCategory['rightNum'];
            $currentPointNum['rightNum'] = $parentCategory['rightNum'] + 1;
            $this->updateCategoryPointNumByRightNum($parentCategory['rightNum']);
            $this->updateCategory($category['id'], $currentPointNum);
            $this->updateCategory($parentCategory['id'], $parentPointNum);
        }

        return $category;
    }

    public function updateCategoryPointNumByRightNum($rightNum)
    {
        $this->getCategoryTestDao()->refreshCategoryLeftNumByRightNum($rightNum);
        $this->getCategoryTestDao()->refreshCategoryRightNumByRightNum($rightNum);
    }

    public function updateCategoryPointNumByRightNumWhenDel($rightNum, $num)
    {
        $this->getCategoryTestDao()->refreshCategoryLeftNumByRightNumWhenDel($rightNum, $num);
        $this->getCategoryTestDao()->refreshCategoryRightNumByRightNumWhenDel($rightNum, $num);
    }
    /*end*/

    protected function getLastCategory()
    {
        return $this->getCategoryTestDao()->getLastCategory();
    }

    protected function setCategoryOrg($category)
    {
        $magic = $this->getSettingService()->get('magic');

        if (empty($magic['enable_org'])) {
            return $category;
        }

        $user = $this->getCurrentUser();
        $currentOrg = $user['org'];

        if (empty($category['parentId'])) {
            if (empty($user['org'])) {
                return $category;
            }
            $category['orgId'] = $currentOrg['id'];
            $category['orgCode'] = $currentOrg['orgCode'];
        } else {
            $parentOrg = $this->getCategory($category['parentId']);
            $category['orgId'] = $parentOrg['orgId'];
            $category['orgCode'] = $parentOrg['orgCode'];
        }

        return $category;
    }

    public function updateCategory($id, array $fields)
    {
        $category = $this->getCategory($id);

        if (empty($category)) {
            $this->createNewException(CategoryException::NOTFOUND_CATEGORY());
        }

        $fields = ArrayToolkit::parts($fields, array('leftNum', 'rightNum', 'description', 'name', 'code', 'weight', 'parentId', 'icon'));

        if (empty($fields)) {
            $this->createNewException(CommonException::ERROR_PARAMETER());
        }

        // filterCategoryFields里有个判断，需要用到这个$fields['groupId']
        $fields['groupId'] = $category['groupId'];

        $this->filterCategoryFields($fields, $category);

        $category = $this->getCategoryTestDao()->update($id, $fields);

        return $category;
    }

    public function deleteCategory($id)
    {
        $category = $this->getCategory($id);

        if (empty($category)) {
            $this->createNewException(CategoryException::NOTFOUND_CATEGORY());
        }

        /*$ids = $this->findCategoryChildrenIds($id);
        $ids[] = $id;

        foreach ($ids as $id) {
            $this->getCategoryTestDao()->delete($id);
        }*/
        $this->getCategoryTestDao()->deleteCategoryByLeftAndRight($category['leftNum'], $category['rightNum']);
        $this->updateCategoryPointNumByRightNumWhenDel($category['rightNum'], $category['rightNum'] - $category['leftNum'] + 1);

    }

    public function getGroup($id)
    {
        return $this->getGroupDao()->get($id);
    }

    public function getGroupByCode($code)
    {
        return $this->getGroupDao()->getByCode($code);
    }

    public function getGroups($start, $limit)
    {
        return $this->getGroupDao()->find($start, $limit);
    }

    public function findAllGroups()
    {
        return $this->getGroupDao()->findAll();
    }

    public function addGroup(array $group)
    {
        return $this->getGroupDao()->create($group);
    }

    public function deleteGroup($id)
    {
        return $this->getGroupDao()->delete($id);
    }

    protected function makeCategoryTree(&$tree, &$categories, $parentId)
    {
        static $depth = 0;

        if (isset($categories[$parentId]) && is_array($categories[$parentId])) {
            foreach ($categories[$parentId] as $category) {
                ++$depth;
                $category['depth'] = $depth;
                $tree[] = $category;
                $this->makeCategoryTree($tree, $categories, $category['id']);
                --$depth;
            }
        }

        return $tree;
    }

    protected function filterCategoryFields(&$category, $relatedCategory = null)
    {
        foreach (array_keys($category) as $key) {
            switch ($key) {
                case 'name':
                    $category['name'] = (string) $category['name'];

                    if (empty($category['name'])) {
                        $this->createNewException(CategoryException::EMPTY_NAME());
                    }

                    break;
                case 'code':
                    if (empty($category['code'])) {
                        $this->createNewException(CategoryException::EMPTY_CODE());
                    } else {
                        if (!preg_match('/^[a-zA-Z0-9_]+$/i', $category['code'])) {
                            $this->createNewException(CategoryException::CODE_INVALID());
                        }

                        if (ctype_digit($category['code'])) {
                            $this->createNewException(CategoryException::CODE_DIGIT_INVALID());
                        }

                        $exclude = empty($relatedCategory['code']) ? null : $relatedCategory['code'];
                        if (!$this->isCategoryCodeAvailable($category['code'], $exclude)) {
                            $this->createNewException(CategoryException::CODE_UNAVAILABLE());
                        }
                    }

                    break;
                case 'groupId':
                    $category['groupId'] = (int) $category['groupId'];
                    $group = $this->getGroup($category['groupId']);

                    if (empty($group)) {
                        $this->createNewException(CategoryException::NOTFOUND_GROUP());
                    }

                    break;
                case 'parentId':
                    $category['parentId'] = (int) $category['parentId'];

                    if ($category['parentId'] > 0) {
                        $parentCategory = $this->getCategory($category['parentId']);

                        if (empty($parentCategory) || $parentCategory['groupId'] != $category['groupId']) {
                            $this->createNewException(CategoryException::NOTFOUND_PARENT_CATEGORY());
                        }
                    }

                    break;
            }
        }

        return $category;
    }

    /**
     * @return CategoryGroupDao
     */
    protected function getGroupDao()
    {
        return $this->createDao('Taxonomy:CategoryGroupDao');
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->biz->service('System:LogService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->biz->service('System:SettingService');
    }

    /**
     * @return CategoryTestDao
     */
    protected function getCategoryTestDao()
    {
        return $this->createDao('CategoryTestPlugin:CategoryTest:CategoryTestDao');
    }
}