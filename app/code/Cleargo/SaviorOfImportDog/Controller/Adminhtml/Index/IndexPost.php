<?php


namespace Cleargo\SaviorOfImportDog\Controller\Adminhtml\Index;

class IndexPost extends \Magento\Backend\App\Action
{

    protected $resultPageFactory;

    protected $resultRawFactory;

    protected $helper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Cleargo\SaviorOfImportDog\Helper\Data $helper
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->helper = $helper;
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->_objectManager->create('Magento\MediaStorage\Model\File\Uploader', ['fileId' => 'file']);
        $param = $this->getRequest()->getParams();
        $file = $this->getRequest()->getFiles('file');
        $csvString = file_get_contents($file['tmp_name']);
        $lines = explode("\r\n", $csvString);
        $array = array();
        foreach ($lines as $line) {
            $array[] = str_getcsv($line, ',', '"');
        }
        unset($param['form_key']);
        unset($param['key']);
        $this->helper->setConfig($param);
        $result = $this->helper->processCSV($array);
        $fileName = 'converted_csv_' . date('dmYHis').'.csv';
        $fp = fopen(__DIR__.'/'.'file.csv', 'w');
        foreach ($result as $fields) {
            fputcsv($fp, $fields);
        }
        $csvString=file_get_contents(__DIR__.'/'.'file.csv');
        unlink(__DIR__.'/'.'file.csv');
        return $this->fileFactory->create($fileName,$csvString,\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
    }
}
