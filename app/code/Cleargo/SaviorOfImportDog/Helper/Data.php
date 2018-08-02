<?php


/**
 * Catalog data helper
 */
namespace Cleargo\SaviorOfImportDog\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{

    protected $_objectManager;

    protected $inputColumn;

    protected $outputColumn;

    /**
     * @var \Magento\Catalog\Model\Config
     */
    protected $tdcAttribute;
    /**
     * @var \Magento\Catalog\Api\AttributeSetRepositoryInterface
     */
    protected $attributeSet;
    /**
     * @var \Magento\Catalog\Api\ProductAttributeGroupRepositoryInterface
     */
    protected $productAttributes;

    protected $connection;

    protected $attributeSetCode;

    protected $variationString;

    protected $configurableChild;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    protected $config;
    
    protected $scopeConfig;
    /**
     * @param Magento\Framework\App\Helper\Context $context
     * @param Magento\Framework\ObjectManagerInterface $objectManager
     * @param Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->_objectManager = $objectManager;
        //input file csv header
        $this->inputColumn=["sku","store_view_code","attribute_set_code","parent sku","product_type","product_websites","categories1","categories2","categories3","name","description","short_description","weight","product_online","tax_class_name","visibility","price","special_price","special_price_from_date","special_price_to_date","url_key","meta_title","meta_keywords","meta_description","base_image","base_image_label","small_image","small_image_label","thumbnail_image","thumbnail_image_label","additional_images1","additional_images2","additional_images3","additional_images4","additional_image_labels","swatch_image","swatch_image_label","display_product_options_in","gift_message_available","new_from_date","new_to_date","map_price","msrp_price","map_enabled","configurable_options","qty"];
        $this->outputColumn=["sku","store_view_code","attribute_set_code","product_type","categories","product_websites","name","description","short_description","weight","product_online","tax_class_name","visibility","price","special_price","special_price_from_date","special_price_to_date","url_key","meta_title","meta_keywords","meta_description","base_image","base_image_label","small_image","small_image_label","thumbnail_image","thumbnail_image_label","swatch_image","swatch_image_label","created_at","updated_at","new_from_date","new_to_date","display_product_options_in","map_price","msrp_price","map_enabled","gift_message_available","custom_design","custom_design_from","custom_design_to","custom_layout_update","page_layout","product_options_container","msrp_display_actual_price_type","country_of_manufacture","qty","out_of_stock_qty","use_config_min_qty","is_qty_decimal","allow_backorders","use_config_backorders","min_cart_qty","use_config_min_sale_qty","max_cart_qty","use_config_max_sale_qty","is_in_stock","notify_on_stock_below","use_config_notify_stock_qty","manage_stock","use_config_manage_stock","use_config_qty_increments","qty_increments","use_config_enable_qty_inc","enable_qty_increments","is_decimal_divided","website_id","deferred_stock_update","use_config_deferred_stock_update","related_skus","related_position","crosssell_skus","crosssell_position","upsell_skus","upsell_position","additional_images","additional_image_labels","hide_from_product_page","bundle_price_type","bundle_sku_type","bundle_price_view","bundle_weight_type","bundle_values","bundle_shipment_type","configurable_variations","configurable_variation_labels","associated_skus"];
        $this->tdcAttribute = $objectManager->get('Magento\Catalog\Model\Config');
        $this->attributeSet = $objectManager->get('Magento\Catalog\Api\AttributeSetRepositoryInterface');
        $this->productAttributes = $objectManager->get('Magento\Catalog\Api\ProductAttributeGroupRepositoryInterface');
        $this->categoryRepository=$objectManager->get('Magento\Catalog\Api\CategoryRepositoryInterface');
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $this->connection = $resource->getConnection();
        $this->attributeSetCode=[];
        $this->configProduct=[];
        $this->configurableChild=[];
        $this->scopeConfig=$objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
        parent::__construct($context);
    }
    
    public function getAutoAssign(){
        return $this->scopeConfig->getValue('import/import/assign_category',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function processCSV($csvArray){
        $this->inputColumn=$csvArray[0];
        //result csv header
        $resultArray=[];
        //store configurable child
        $variationString=[];
        foreach ($csvArray as $key=>$value){
            if($key==0){
                continue;
            }
            if(sizeof($value)<=1){//skip empty row
                continue;
            }
            $array=$this->createProductArray($value);
            $configurableAttribute=explode(',',$csvArray[$key][$this->findInputIndex('configurable_options')]);
            //get configurable_options
//            echo $value[$this->findInputIndex('configurable_options')].'<br />';
            //process configurable product configurable_variations eg. sku=test_treat1-30mins HK$ 780-1,treatment_type=Treatment Whole Body: 30mins HK$ 780|sku=test_treat1-45mins HK$ 1,170-1,treatment_type=Treatment Whole Body: 45mins HK$ 1,170|sku=test_treat1-60mins HK$ 1,500-1,treatment_type=Treatment Whole Body: 60mins HK$ 1,500
//            foreach ($this->configurableChild as $key2=>$value2){
//                $variationString[$key2]='';
//                foreach($value2 as $key4=>$value4) {
//                    $string = '';
//                    $temp = array();
//                    $temp['sku'] = $value4[$this->findInputIndex('sku')];
//                    $string .= 'sku=' . $value4[$this->findInputIndex('sku')];
//                    foreach ($configurableAttribute as $key3 => $value3) {
//                        $temp[$value3] = $value4[$this->findInputIndex($value3)];
//                        $string .=','.$value3 . '=' . $value4[$this->findInputIndex($value3)] ;
//                    }
//                    $variation[] = $temp;
//                    $variationTemp[]=$string;
//                }
////                if($key2=='CZM173C25489'){
////                    var_dump($configurableAttribute);
////                }
//                $variationString[$key2]=implode(' | ',$variationTemp);
//            }
//            $variation=[];
            $resultArray[]=$array;
        }
//        echo json_encode($variationString['CZM173C25489']);
//        exit;
//        echo json_encode($variationString);
//        exit;
        //create configurable product
        foreach ($this->configurableChild as $key2=>$value2){
            $configProduct=$this->createProductArray($value2[0],true);
            $configProduct[$this->findOutputIndex('sku')]=$key2;
            $configProduct[$this->findOutputIndex('product_type')]='configurable';
            $configurableAttribute=explode(',',$value2[0][$this->findInputIndex('configurable_options')]);
            $variationTemp=array();
            foreach($value2 as $key4=>$value4) {
                $string='';
                $string .= 'sku=' . $value4[$this->findInputIndex('sku')];
                foreach ($configurableAttribute as $key3 => $value3) {
                    $temp[$value3] = $value4[$this->findInputIndex($value3)];
                    $string .= ',' . $value3 . '=' . $value4[$this->findInputIndex($value3)];
                }
                $variationTemp[]=$string;
            }
            $configProduct[$this->findOutputIndex('configurable_variations')]=implode(' | ',$variationTemp);
            $configProduct[$this->findOutputIndex('visibility')]='Catalog, Search';
            $configProduct[$this->findOutputIndex('store_view_code')]='';
            $configProduct[$this->findOutputIndex('url_key')]=str_replace(' ', '-', trim($value2[0][$this->findInputIndex('name')]) . '-' . trim($key2));
            $this->configProduct[]=$configProduct;
        }
        array_unshift($resultArray ,$this->outputColumn);
        foreach ($this->configProduct as $key2=>$value2) {
            array_push($resultArray,$value2);
        }
        return $resultArray;
    }

    public function findInputIndex($name){
        return array_search(trim($name),$this->inputColumn);
    }
    public function findOutputIndex($name){
        return array_search(trim($name),$this->outputColumn);
    }

    public function createProductArray($value,$noAdd=false){
        $array=[];
        $array[$this->findOutputIndex('sku')]=$value[$this->findInputIndex('sku')];//sku
        $array[$this->findOutputIndex('store_view_code')]=$value[$this->findInputIndex('store_view_code')];
        $array[$this->findOutputIndex('attribute_set_code')]=$value[$this->findInputIndex('attribute_set_code')];
        $array[$this->findOutputIndex('product_type')]=$value[$this->findInputIndex('product_type')];
        $array[$this->findOutputIndex('name')]=$value[$this->findInputIndex('name')];
        $array[$this->findOutputIndex('description')]=$value[$this->findInputIndex('description')];
        $array[$this->findOutputIndex('short_description')]=$value[$this->findInputIndex('short_description')];
        $array[$this->findOutputIndex('weight')]=$value[$this->findInputIndex('weight')];
        $array[$this->findOutputIndex('product_online')]=$value[$this->findInputIndex('product_online')];
        $array[$this->findOutputIndex('tax_class_name')]=$value[$this->findInputIndex('tax_class_name')];
        $array[$this->findOutputIndex('visibility')]=$value[$this->findInputIndex('visibility')];
        $array[$this->findOutputIndex('price')]=$value[$this->findInputIndex('price')];
        $array[$this->findOutputIndex('special_price')]=$value[$this->findInputIndex('special_price')];
        $array[$this->findOutputIndex('special_price_from_date')]=$value[$this->findInputIndex('special_price_from_date')];
        $array[$this->findOutputIndex('special_price_to_date')]=$value[$this->findInputIndex('special_price_to_date')];
        if(empty($value[$this->findInputIndex('url_key')])) {
            $array[$this->findOutputIndex('url_key')] = str_replace(' ', '-', trim($value[$this->findInputIndex('name')]) . '-' . trim($value[$this->findInputIndex('sku')]));
        }else{
            $array[$this->findOutputIndex('url_key')]=$value[$this->findInputIndex('url_key')];
        }
        $array[$this->findOutputIndex('url_key')]=strtolower($array[$this->findOutputIndex('url_key')]);
        $array[$this->findOutputIndex('meta_title')]=$value[$this->findInputIndex('meta_title')];
        $array[$this->findOutputIndex('meta_keywords')]=$value[$this->findInputIndex('meta_keywords')];
        $array[$this->findOutputIndex('meta_description')]=$value[$this->findInputIndex('meta_description')];
        $array[$this->findOutputIndex('base_image')]=$value[$this->findInputIndex('base_image')];
        $array[$this->findOutputIndex('base_image_label')]=$value[$this->findInputIndex('base_image_label')];
        $array[$this->findOutputIndex('small_image')]=$value[$this->findInputIndex('small_image')];
        $array[$this->findOutputIndex('small_image_label')]=$value[$this->findInputIndex('small_image_label')];
        $array[$this->findOutputIndex('thumbnail_image')]=$value[$this->findInputIndex('thumbnail_image')];
        $array[$this->findOutputIndex('thumbnail_image_label')]=$value[$this->findInputIndex('thumbnail_image_label')];
        $array[$this->findOutputIndex('swatch_image')]=$value[$this->findInputIndex('swatch_image')];
        $array[$this->findOutputIndex('swatch_image_label')]=$value[$this->findInputIndex('swatch_image_label')];
        $array[$this->findOutputIndex('display_product_options_in')]=$value[$this->findInputIndex('display_product_options_in')];
        $array[$this->findOutputIndex('gift_message_available')]=$value[$this->findInputIndex('gift_message_available')];
        $array[$this->findOutputIndex('new_from_date')]=$value[$this->findInputIndex('new_from_date')];
        $array[$this->findOutputIndex('new_to_date')]=$value[$this->findInputIndex('new_to_date')];
        $array[$this->findOutputIndex('map_price')]=$value[$this->findInputIndex('map_price')];
        $array[$this->findOutputIndex('msrp_price')]=$value[$this->findInputIndex('msrp_price')];
        $array[$this->findOutputIndex('map_enabled')]=$value[$this->findInputIndex('map_enabled')];
        $array[$this->findOutputIndex('qty')]=$value[$this->findInputIndex('qty')];
        //other option
        foreach ($this->config as $key2=>$value2){
            $array[$this->findOutputIndex($key2)]=$value2;
        }
        $array[$this->findOutputIndex('product_websites')]=$this->getConfig()['website_id'];
        $array[$this->findOutputIndex('created_at')]='';
        $array[$this->findOutputIndex('updated_at')]='';
        $array[$this->findOutputIndex('custom_design')]='';
        $array[$this->findOutputIndex('custom_design_from')]='';
        $array[$this->findOutputIndex('custom_design_to')]='';
        $array[$this->findOutputIndex('custom_layout_update')]='';
        $array[$this->findOutputIndex('page_layout')]='';
        $array[$this->findOutputIndex('product_options_container')]='';
        $array[$this->findOutputIndex('msrp_display_actual_price_type')]='';
        $array[$this->findOutputIndex('country_of_manufacture')]='';
        $array[$this->findOutputIndex('hide_from_product_page')]='';
        $array[$this->findOutputIndex('bundle_price_type')]='';
        $array[$this->findOutputIndex('bundle_sku_type')]='';
        $array[$this->findOutputIndex('bundle_price_view')]='';
        $array[$this->findOutputIndex('bundle_weight_type')]='';
        $array[$this->findOutputIndex('bundle_values')]='';
        $array[$this->findOutputIndex('bundle_shipment_type')]='';
        $array[$this->findOutputIndex('configurable_variations')]='';
        $array[$this->findOutputIndex('configurable_variation_labels')]='';

        //store parent sku and sku
        $parentSku=$this->findInputIndex('parent_sku');
        $sku=$this->findInputIndex('sku');
        if($noAdd==false) {
            if (!empty($value[$parentSku])) {
                if (isset($this->configurableChild[$value[$parentSku]]) && is_array($this->configurableChild[$value[$parentSku]])) {
                } else {
                    $this->configurableChild[$value[$parentSku]] = array();
                }
                $parent_sku = $value[$parentSku];
                $this->configurableChild[$parent_sku][] = $value;
            }
        }
        //get categories categories
        $categoryIndex=1;
        $categoryArray=[];
        while(($index=$this->findInputIndex('categories'.$categoryIndex++))!==false){
            if(!empty($value[$index])) {
                $category=$this->categoryRepository->get($value[$index]);
                $path=$category->getPath();
                $path=explode('/',$path);
                $categoryTemp=[];
                foreach ($path as $key2=>$value2){
                    if(in_array($value2,[1])){
                        continue;
                    }
                    $temp=$this->categoryRepository->get($value2);
                    $categoryTemp[]=$temp->getName();
                }
                $categoryArray[] = implode('/',$categoryTemp);
            }
        }
        $array[$this->findOutputIndex('categories')]=implode('|',$categoryArray);
        //get additional_image additional_images
        $additionalImageIndex=1;
        $additionalImageArray=[];
        while(($index=$this->findInputIndex('additional_images'.$additionalImageIndex++))!==false){
            if(!empty($value[$index])) {
                $additionalImageArray[] = $value[$index];
            }
        }
        $array[$this->findOutputIndex('additional_images')]=implode(',',$additionalImageArray);
        //get additional_image_label
        $additionalImageLabelIndex=1;
        $additionalImageLabelArray=[];
        while(($index=$this->findInputIndex('additional_image_labels'.$additionalImageLabelIndex++))!==false){
            if(!empty($value[$index])) {
                $additionalImageLabelArray[]=$value[$index];
            }
        }
        $array[$this->findOutputIndex('additional_image_labels')]=implode(',',$additionalImageLabelArray);
        //get related_skus related_skus
        $relatedSkuLabelIndex=1;
        $relatedSkuLabelArray=[];
        while(($index=$this->findInputIndex('related_skus'.$relatedSkuLabelIndex++))!==false){
            if(!empty($value[$index])) {
                $relatedSkuLabelArray[] = $value[$index];
            }
        }
        $array[$this->findOutputIndex('related_skus')]=implode(',',$relatedSkuLabelArray);
        //get crosssell_skus crosssell_skus
        $crosssellSkuLabelIndex=1;
        $crosssellSkuLabelArray=[];
        while(($index=$this->findInputIndex('crosssell_skus'.$crosssellSkuLabelIndex++))!==false){
            if(!empty($value[$index])) {
                $crosssellSkuLabelArray[] = $value[$index];
            }
        }
        $array[$this->findOutputIndex('crosssell_skus')]=implode(',',$crosssellSkuLabelArray);
        //get upsell_skus upsell_skus
        $upsellSkuLabelIndex=1;
        $upsellSkuLabelArray=[];
        while(($index=$this->findInputIndex('upsell_skus'.$upsellSkuLabelIndex++))!==false){
            if(!empty($value[$index])) {
                $upsellSkuLabelArray[] = $value[$index];
            }
        }
        $array[$this->findOutputIndex('upsell_skus')]=implode(',',$upsellSkuLabelArray);
        //get associated_skus associated_skus
        $associatedSkuLabelIndex=1;
        $associatedSkuLabelArray=[];
        while(($index=$this->findInputIndex('associated_skus'.$associatedSkuLabelIndex++))!==false){
            if(!empty($value[$index])) {
                $associatedSkuLabelArray[]=$value[$index];
            }
        }
        $array[$this->findOutputIndex('associated_skus')]=implode(',',$associatedSkuLabelArray);
        //get custom attribute depend attribtue set
        //get attribute set id
        $query=$this->connection->prepare('select attribute_set_id from eav_attribute_set where attribute_set_name=? and entity_type_id=4');
        $query->bindValue(1,$value[$this->findInputIndex('attribute_set_code')]);
        $query->execute();
        $result=$query->fetch();
        $setId=$result['attribute_set_id'];
        $this->attributeSetCode[$setId]=array();
//            echo "SELECT * FROM eav_entity_attribute where attribute_set_id=".intval($setId).' and entity_type_id=4';
        $result = $this->connection->fetchAll("SELECT * FROM eav_entity_attribute where attribute_set_id=".intval($setId).' and entity_type_id=4');
        foreach ($result as $key2=>$value2){
            /**
             *  @var \Magento\Eav\Model\Entity\Attribute $attributeInfo
             **/
            $attributeInfo=$this->_objectManager->get('Magento\Eav\Model\Entity\Attribute');
            $attributeInfo->load($value2['attribute_id']);
            if(intval($attributeInfo->getIsUserDefined())==0){
                continue;
            }
            $attributeCode=$attributeInfo->getAttributeCode();
            $this->attributeSetCode[$setId][]=$attributeCode;
            if($this->findOutputIndex($attributeCode)===false) {
                $this->outputColumn[] = $attributeCode;
            }
            if(in_array($attributeInfo->getFrontendInput(),['select','multiselect'])){
                $attributeIndex=1;
                $attributeArray=[];
                while(($index=$this->findInputIndex($attributeCode.$attributeIndex++))!==false){
                    if(!empty(trim($value[$index]))) {
                        $attributeArray[] = $value[$index];
                    }
                }
                if($attributeInfo->getFrontendInput()=='select'){
                    if($this->findInputIndex($attributeCode)>0){
                        $array[$this->findOutputIndex($attributeCode)] = trim($value[$this->findInputIndex($attributeCode)]);
                    }else{
                        $array[$this->findOutputIndex($attributeCode)] = '';
                    }
                }else{
                    $array[$this->findOutputIndex($attributeCode)] = implode(',',$attributeArray);
                }
            }else {
                if($this->findInputIndex($attributeCode)!==false) {
                    if (isset($value[$this->findInputIndex($attributeCode)])) {
                        $array[$this->findOutputIndex($attributeCode)] = trim($value[$this->findInputIndex($attributeCode)]);
                    } else {
                        $array[$this->findOutputIndex($attributeCode)] = '';
                    }
                }else{
                    $array[$this->findOutputIndex($attributeCode)] = '';
                }
            }
        }
        //get related_position
        if(sizeof($relatedSkuLabelArray)>0) {
            $relatedSkuPosition = range(1, sizeof($relatedSkuLabelArray));
            $array[$this->findOutputIndex('related_position')] = implode(',', $relatedSkuPosition);
        }else{
            $array[$this->findOutputIndex('related_position')] = '';
        }
        //get crosssell_position
        if(sizeof($crosssellSkuLabelArray)>0) {
            $crosssellPosition = range(1, sizeof($crosssellSkuLabelArray));
            $array[$this->findOutputIndex('crosssell_position')] = implode(',', $crosssellPosition);
        }else{
            $array[$this->findOutputIndex('crosssell_position')] = '';
        }
        //get upsell_position
        if(sizeof($upsellSkuLabelArray)>0) {
            $upsellPosition = range(1, sizeof($upsellSkuLabelArray));
            $array[$this->findOutputIndex('upsell_position')] = implode(',', $upsellPosition);
        }else{
            $array[$this->findOutputIndex('upsell_position')] = '';
        }
        ksort($array);
        return $array;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }


} 
