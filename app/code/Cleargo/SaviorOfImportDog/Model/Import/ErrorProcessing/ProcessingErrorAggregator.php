<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Cleargo\SaviorOfImportDog\Model\Import\ErrorProcessing;

/**
 * Import/Export Error Aggregator class
 */
class ProcessingErrorAggregator extends \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregator
{

    public function __construct(
        \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorFactory $errorFactory
    ) {
        parent::__construct($errorFactory);
        $this->allowedErrorsCount=9999999;
    }
    /**
     * @param string $validationStrategy
     * @param int $allowedErrorCount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initValidationStrategy($validationStrategy, $allowedErrorCount = 0)
    {
        $allowedStrategy = [
            self::VALIDATION_STRATEGY_STOP_ON_ERROR,
            self::VALIDATION_STRATEGY_SKIP_ERRORS
        ];
        if (!in_array($validationStrategy, $allowedStrategy)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('ImportExport: Import Data validation - Validation strategy not found')
            );
        }
        $this->validationStrategy = $validationStrategy;
        $this->allowedErrorsCount = 9999999;

        return $this;
    }
}
