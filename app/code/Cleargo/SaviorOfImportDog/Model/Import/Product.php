<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Cleargo\SaviorOfImportDog\Model\Import;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface as ValidatorInterface;
use Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor;
use Magento\Framework\Stdlib\DateTime;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\Catalog\Model\Product\Visibility;

/**
 * Import entity product model
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Product extends \Magento\CatalogImportExport\Model\Import\Product
{
    /**
     * @param array $rowData
     * @return array
     */
    public function getImagesFromRow(array $rowData)
    {
        $images = [];
        $labels = [];
        $seprator=$this->getMultipleValueSeparator();
        if($this->scopeConfig->getValue('import/import/fix',\Magento\Store\Model\ScopeInterface::SCOPE_STORE)){
            $seprator=',';
        }
        foreach ($this->_imagesArrayKeys as $column) {
            $images[$column] = [];
            $labels[$column] = [];
            if (!empty($rowData[$column])) {
                $images[$column] = array_unique(
                    explode($seprator, $rowData[$column])//hard code comma as url cant support | char
                );
            }

            if (!empty($rowData[$column . '_label'])) {
                $labels[$column] = explode($seprator, $rowData[$column . '_label']);
            }

            if (count($labels[$column]) > count($images[$column])) {
                $labels[$column] = array_slice($labels[$column], 0, count($images[$column]));
            } elseif (count($labels[$column]) < count($images[$column])) {
                $labels[$column] = array_pad($labels[$column], count($images[$column]), '');
            }
        }

        return [$images, $labels];
    }
    public function parseMultiselectValues($values, $delimiter = self::PSEUDO_MULTI_LINE_SEPARATOR)
    {
        $delimiter=',';
//        if (empty($this->_parameters[Import::FIELDS_ENCLOSURE])) {
        //return comma sperate array directly
            return explode($delimiter, strtolower($values));
//        }
//        if (preg_match_all('~"((?:[^"]|"")*)"~', $values, $matches)) {
//            return $values = array_map(function ($value) {
//                return str_replace('""', '"', $value);
//            }, $matches[1]);
//        }
//        return [$values];
    }


    /**
     * @param array $rowData
     * @return bool
     */
    protected function isNeedToValidateUrlKey($rowData)
    {
        //add tmp for TC file @by leo 20180306
        //when there is store view code, but not set url_key, dont check the duplicate url which has magento bug
        if (empty($rowData[self::URL_KEY]) && isset($rowData[self::COL_STORE_VIEW_CODE])) {
            return false;
        }

        return (!empty($rowData[self::URL_KEY]) || !empty($rowData[self::COL_NAME]))
        && (empty($rowData[self::COL_VISIBILITY])
            || $rowData[self::COL_VISIBILITY]
            !== (string)Visibility::getOptionArray()[Visibility::VISIBILITY_NOT_VISIBLE]);
    }

}
