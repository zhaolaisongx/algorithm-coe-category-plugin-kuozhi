<?php

namespace CategoryTestPlugin\Command;

use AppBundle\Command\BaseCommand;
use Biz\Taxonomy\Service\CategoryService;
use CategoryTestPlugin\Biz\CategoryTest\Service\CategoryTestService;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateDataCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('category_test:create_test_data')
            ->addArgument('namePrefix', 1, '分类名字前缀')
            ->addArgument('codePrefix', 1, '编码前缀')
            ->addArgument('num', 1, '生成的测试数据数量')
            ->addArgument('type', 1, 'normal: 普通分类数据,test: 测试的嵌套数据');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>开始创建数据</info>');

        if ($input->getArgument('type') == 'normal') {
            $this->initData($output, $input->getArguments());
        } elseif ($input->getArgument('type') == 'test') {
            $this->initTestData($output, $input->getArguments());
        } else {
            $output->writeln('<info>请选择正确的数据类型创建</info>');
            return;
        }

        $output->writeln('<info>创建测试数据完毕</info>');
    }

    protected function initData($output, $args)
    {
        $output->write('正在创建普通分类测试数据');
        $time = time();
        $courseGroup = $this->getCategoryService()->getGroupByCode('course');

        $category = array(
            'id' => 0,
            'name' => '',
            'code' => '',
            'description' => '',
            'groupId' => $courseGroup['id'],
            'parentId' => 0,
            'weight' => 0,
            'icon' => '',
        );

        $newCategory = array();
        for ($i = 1; $i <= $args['num']; $i ++) {
            $newData = array(
                'name' => $args['namePrefix'].$i,
                'code' => $args['codePrefix'].$i,
            );
            if ($i % 3 != 0 && $i > 1) {
                $newData['parentId'] = $newCategory['id'];
            }
            $newCategory = $this->getCategoryService()->createCategory(array_merge($category, $newData));

            if ($i % 100 == 0) {
                $output->writeln(' ...<info>第'.$i.'条数据成功...</info>');
            }
        }

        $output->writeln(' ...<info>成功'.(time()-$time).'秒</info>');
    }


    protected function initTestData($output, $args)
    {
        $output->write('正在创建嵌套测试数据');
        $time = time();
        $testGroup = $this->getCategoryService()->getGroupByCode('test');

        $testCategory = array(
            'id' => 0,
            'name' => '',
            'code' => '',
            'description' => '',
            'groupId' => $testGroup['id'],
            'parentId' => 0,
            'weight' => 0,
            'icon' => '',
        );

        $newTestCategory = array();
        for ($i = 1; $i <= $args['num']; $i ++) {
            $newTestData = array(
                'name' => $args['namePrefix'].$i,
                'code' => $args['codePrefix'].$i,
            );
            if ($i % 3 != 0 && $i > 1) {
                $newTestData['parentId'] = $newTestCategory['id'];
            }
            $newTestCategory = $this->getCategoryTestService()->createCategory(array_merge($testCategory, $newTestData));

            if ($i % 100 == 0) {
                $output->writeln(' ...<info>第'.$i.'条数据成功...</info>');
            }
        }

        $output->writeln(' ...<info>成功'.(time()-$time).'秒</info>');
    }

    /**
     * @return CategoryService
     */
    protected function getCategoryService()
    {
        return ServiceKernel::instance()->getBiz()->service('Taxonomy:CategoryService');
    }

    /**
     * @return CategoryTestService
     */
    protected function getCategoryTestService()
    {
        return ServiceKernel::instance()->getBiz()->service('CategoryTestPlugin:CategoryTest:CategoryTestService');
    }

}