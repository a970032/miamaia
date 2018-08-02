<?php


namespace Cleargo\SaviorOfImportDog\Block\Adminhtml\Index;

class Index extends \Magento\Framework\View\Element\Template
{

    protected $_objectManager;
    protected $_storeManager;
    protected $_helper;
    protected $connection;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_storeManager = $storeManager;
        $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $this->connection = $resource->getConnection();
    }
    public function getAllWebsiteId(){
        $query=$this->connection->prepare('SELECT * FROM store_website where website_id!=0');
        $query->execute();
        return $query->fetchAll();
    }
}
