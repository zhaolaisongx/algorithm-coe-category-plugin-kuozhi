<?php

namespace CategoryTestPlugin\Controller;

use Biz\Taxonomy\CategoryException;
use AppBundle\Controller\Admin\CategoryController as BaseController;
use CategoryTestPlugin\Biz\CategoryTest\Service\CategoryTestService;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends BaseController
{
    public function embedAction($group, $layout, $menu = null)
    {
        $group = $this->getCategoryTestService()->getGroupByCode($group);
        if (empty($group)) {
            $this->createNewException(CategoryException::NOTFOUND_GROUP());
        }

        $categories = $this->getCategoryTestService()->getCategoryStructureTree($group['id']);

        return $this->render('CategoryTestPlugin:category:embed.html.twig', array(
            'group' => $group,
            'menu' => $menu,
            'categories' => $categories,
            'layout' => $layout,
        ));
    }

    public function createAction(Request $request)
    {
        if ('POST' == $request->getMethod()) {
            $category = $this->getCategoryTestService()->createCategory($request->request->all());

            return $this->renderTbody($category['groupId']);
        }

        $category = array(
            'id' => 0,
            'name' => '',
            'code' => '',
            'description' => '',
            'groupId' => (int) $request->query->get('groupId'),
            'parentId' => (int) $request->query->get('parentId', 0),
            'weight' => 0,
            'icon' => '',
        );

        return $this->render('CategoryTestPlugin:category:modal.html.twig', array(
            'category' => $category,
        ));
    }

    public function editAction(Request $request, $id)
    {
        $category = $this->getCategoryTestService()->getCategory($id);

        if (empty($category)) {
            $this->createNewException(CategoryException::NOTFOUND_CATEGORY());
        }

        if ('POST' == $request->getMethod()) {
            $category = $this->getCategoryTestService()->updateCategory($id, $request->request->all());

            return $this->renderTbody($category['groupId']);
        }

        return $this->render('CategoryTestPlugin:category:modal.html.twig', array(
            'category' => $category,
        ));
    }

    public function deleteAction(Request $request, $id)
    {
        $category = $this->getCategoryTestService()->getCategory($id);

        if (empty($category)) {
            $this->createNewException(CategoryException::NOTFOUND_CATEGORY());
        }

        $this->getCategoryTestService()->deleteCategory($id);

        return $this->renderTbody($category['groupId']);
    }

    public function sortAction(Request $request)
    {
        $ids = $request->request->get('ids');

        if (!empty($ids)) {
            $this->getCategoryTestService()->sortCategories($ids);
        }

        return $this->createJsonResponse(true);
    }

    public function checkCodeAction(Request $request)
    {
        $code = $request->query->get('value');
        $exclude = $request->query->get('exclude');

        $available = $this->getCategoryTestService()->isCategoryCodeAvailable($code, $exclude);

        if ($available) {
            $response = array('success' => true, 'message' => '');
        } else {
            $response = array('success' => false, 'message' => '编码已被占用，请换一个。');
        }

        return $this->createJsonResponse($response);
    }

    protected function renderTbody($groupId)
    {
        $group = $this->getCategoryTestService()->getGroup($groupId);
        $categories = $this->getCategoryTestService()->getCategoryStructureTree($groupId);

        return $this->render('CategoryTestPlugin:category:tbody.html.twig', array(
            'categories' => $categories,
            'group' => $group,
        ));
    }

    /**
     * @return CategoryTestService
     */
    protected function getCategoryTestService()
    {
        return $this->createService('CategoryTestPlugin:CategoryTest:CategoryTestService');
    }
}