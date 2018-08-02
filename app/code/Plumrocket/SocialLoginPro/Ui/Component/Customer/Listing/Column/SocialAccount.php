<?php

namespace Plumrocket\SocialLoginPro\Ui\Component\Customer\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class SocialAccount extends Column
{

    protected $layout;

    protected $accountFactory;

    public function __construct(
        \Plumrocket\SocialLoginPro\Model\AccountFactory $accountFactory,
        \Magento\Framework\View\LayoutFactory $layout,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->accountFactory = $accountFactory;
        $this->layout = $layout;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $accounts = $this->accountFactory->create()->getCollection()
                    ->addFieldToFilter('customer_id', $item['entity_id']);

                if ($accounts->count()) {
                    $item[$this->getData('name')] = $this->prepareHtml($accounts);
                }
            }
        }
        return $dataSource;
    }

    /**
     * Preparing html for column
     * @param  Plumrocket\SocialLoginPro\Model\RresourceModel\Account\Collection $accounts
     * @return string
     */
    private function prepareHtml($accounts)
    {
        $html = $this->layout
            ->create()
            ->createBlock('Magento\Framework\View\Element\Template')
            ->setAccounts($accounts)
            ->setTemplate('Plumrocket_SocialLoginPro::customer/listing/column/accounts.phtml')
            ->toHtml();

        return $html;
    }
}
