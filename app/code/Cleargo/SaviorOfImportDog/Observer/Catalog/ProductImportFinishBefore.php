<?php

namespace Cleargo\SaviorOfImportDog\Observer\Catalog;

class ProductImportFinishBefore implements \Magento\Framework\Event\ObserverInterface
{


    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $helper=$objectManager->get('Cleargo\SaviorOfImportDog\Helper\Data');
        if($helper->getAutoAssign()!=1){
            return;
        }
        $categoryBuilder=$objectManager->get('Magento\VisualMerchandiser\Model\Category\Builder');
        $categoryRepos=$objectManager->get('Magento\Catalog\Api\CategoryRepositoryInterface');
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $query=$connection->prepare('select category_id from visual_merchandiser_rule where is_active=1');
        $query->execute();
        $rules=$query->fetchAll();
        foreach($rules as $key=>$value) {
            $category = $categoryRepos->get($value['category_id'],4);
            $category->save();
            $categoryBuilder->rebuildCategory($category);
        }
        //Your observer code
    }
}
