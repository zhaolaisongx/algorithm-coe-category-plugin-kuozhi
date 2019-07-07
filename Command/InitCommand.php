<?php

namespace CategoryTestPlugin\Command;

use AppBundle\Command\BaseCommand;
use Biz\Taxonomy\Service\CategoryService;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('category_test:init');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>开始初始化系统</info>');

        $this->initCategory($output);

        $output->writeln('<info>初始化系统完毕</info>');
    }

    protected function initCategory($output) {

        $output->write('  初始化分类分组');

        $testGroup = $this->getCategoryService()->getGroupByCode('test');

        if (!$testGroup) {
            $testGroup = $this->getCategoryService()->addGroup(array(
                'name' => '分类嵌套测试',
                'code' => 'test',
                'depth' => 5,
            ));
        }

        $output->writeln(' ...<info>成功</info>');
    }

    /**
     * @return CategoryService
     */
    protected function getCategoryService()
    {
        return ServiceKernel::instance()->getBiz()->service('Taxonomy:CategoryService');
    }

    protected function getCategoryTestService()
    {
        return ServiceKernel::instance()->getBiz()->service('CategoryTestPlugin:CategoryTest:CategoryTestService');
    }

}