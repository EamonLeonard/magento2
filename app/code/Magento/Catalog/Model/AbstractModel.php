<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Magento
 * @package     Magento_Catalog
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\Catalog\Model;

/**
 * Abstract model for catalog entities
 *
 * @category   Magento
 * @package    Magento_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
abstract class AbstractModel extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Attribute default values
     *
     * This array contain default values for attributes which was redefine
     * value for store
     *
     * @var array
     */
    protected $_defaultValues = array();

    /**
     * This array contains codes of attributes which have value in current store
     *
     * @var array
     */
    protected $_storeValuesFlags = array();

    /**
     * Locked attributes
     *
     * @var array
     */
    protected $_lockedAttributes = array();

    /**
     * Is model deleteable
     *
     * @var boolean
     */
    protected $_isDeleteable = true;

    /**
     * Is model readonly
     *
     * @var boolean
     */
    protected $_isReadonly = false;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Model\Resource\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\Db $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\Resource\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\Db $resourceCollection = null,
        array $data = array()
    ) {
        $this->_storeManager = $storeManager;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Lock attribute
     *
     * @param string $attributeCode
     * @return $this
     */
    public function lockAttribute($attributeCode)
    {
        $this->_lockedAttributes[$attributeCode] = true;
        return $this;
    }

    /**
     * Unlock attribute
     *
     * @param string $attributeCode
     * @return $this
     */
    public function unlockAttribute($attributeCode)
    {
        if ($this->isLockedAttribute($attributeCode)) {
            unset($this->_lockedAttributes[$attributeCode]);
        }

        return $this;
    }

    /**
     * Unlock all attributes
     *
     * @return $this
     */
    public function unlockAttributes()
    {
        $this->_lockedAttributes = array();
        return $this;
    }

    /**
     * Retrieve locked attributes
     *
     * @return array
     */
    public function getLockedAttributes()
    {
        return array_keys($this->_lockedAttributes);
    }

    /**
     * Checks that model have locked attributes
     *
     * @return boolean
     */
    public function hasLockedAttributes()
    {
        return !empty($this->_lockedAttributes);
    }

    /**
     * Retrieve locked attributes
     *
     * @param mixed $attributeCode
     * @return boolean
     */
    public function isLockedAttribute($attributeCode)
    {
        return isset($this->_lockedAttributes[$attributeCode]);
    }

    /**
     * Overwrite data in the object.
     *
     * The $key can be string or array.
     * If $key is string, the attribute value will be overwritten by $value
     *
     * If $key is an array, it will overwrite all the data in the object.
     *
     * $isChanged will specify if the object needs to be saved after an update.
     *
     * @param string|array $key
     * @param mixed $value
     * @return \Magento\Framework\Object
     */
    public function setData($key, $value = null)
    {
        if ($this->hasLockedAttributes()) {
            if (is_array($key)) {
                foreach ($this->getLockedAttributes() as $attribute) {
                    if (isset($key[$attribute])) {
                        unset($key[$attribute]);
                    }
                }
            } elseif ($this->isLockedAttribute($key)) {
                return $this;
            }
        } elseif ($this->isReadonly()) {
            return $this;
        }

        return parent::setData($key, $value);
    }

    /**
     * Unset data from the object.
     *
     * The $key can be a string only. Array will be ignored.
     *
     * $isChanged will specify if the object needs to be saved after an update.
     *
     * @param string $key
     * @return $this
     */
    public function unsetData($key = null)
    {
        if (!is_null($key) && $this->isLockedAttribute($key) || $this->isReadonly()) {
            return $this;
        }

        return parent::unsetData($key);
    }

    /**
     * Get collection instance
     *
     * @return \Magento\Catalog\Model\Resource\Collection\AbstractCollection
     */
    public function getResourceCollection()
    {
        $collection = parent::getResourceCollection()->setStoreId($this->getStoreId());
        return $collection;
    }

    /**
     * Load entity by attribute
     *
     * @param \Magento\Eav\Model\Entity\Attribute\AttributeInterface|integer|string|array $attribute
     * @param null|string|array $value
     * @param string $additionalAttributes
     * @return bool|\Magento\Catalog\Model\AbstractModel
     */
    public function loadByAttribute($attribute, $value, $additionalAttributes = '*')
    {
        $collection = $this->getResourceCollection()->addAttributeToSelect(
            $additionalAttributes
        )->addAttributeToFilter(
            $attribute,
            $value
        )->setPage(
            1,
            1
        );

        foreach ($collection as $object) {
            return $object;
        }
        return false;
    }

    /**
     * Retrieve sore object
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        return $this->_storeManager->getStore($this->getStoreId());
    }

    /**
     * Retrieve all store ids of object current website
     *
     * @return array
     */
    public function getWebsiteStoreIds()
    {
        return $this->getStore()->getWebsite()->getStoreIds(true);
    }

    /**
     * Adding attribute code and value to default value registry
     *
     * Default value existing is flag for using store value in data
     *
     * @param   string $attributeCode
     * @param   mixed  $value
     * @return  $this
     */
    public function setAttributeDefaultValue($attributeCode, $value)
    {
        $this->_defaultValues[$attributeCode] = $value;
        return $this;
    }

    /**
     * Retrieve default value for attribute code
     *
     * @param   string $attributeCode
     * @return  array|boolean
     */
    public function getAttributeDefaultValue($attributeCode)
    {
        return array_key_exists($attributeCode, $this->_defaultValues) ? $this->_defaultValues[$attributeCode] : false;
    }

    /**
     * Set attribute code flag if attribute has value in current store and does not use
     * value of default store as value
     *
     * @param   string $attributeCode
     * @return  $this
     */
    public function setExistsStoreValueFlag($attributeCode)
    {
        $this->_storeValuesFlags[$attributeCode] = true;
        return $this;
    }

    /**
     * Check if object attribute has value in current store
     *
     * @param   string $attributeCode
     * @return  bool
     */
    public function getExistsStoreValueFlag($attributeCode)
    {
        return array_key_exists($attributeCode, $this->_storeValuesFlags);
    }

    /**
     * Before save unlock attributes
     *
     * @return \Magento\Catalog\Model\AbstractModel
     */
    protected function _beforeSave()
    {
        $this->unlockAttributes();
        return parent::_beforeSave();
    }

    /**
     * Checks model is deletable
     *
     * @return boolean
     */
    public function isDeleteable()
    {
        return $this->_isDeleteable;
    }

    /**
     * Set is deletable flag
     *
     * @param boolean $value
     * @return $this
     */
    public function setIsDeleteable($value)
    {
        $this->_isDeleteable = (bool)$value;
        return $this;
    }

    /**
     * Checks model is deletable
     *
     * @return boolean
     */
    public function isReadonly()
    {
        return $this->_isReadonly;
    }

    /**
     * Set is deletable flag
     *
     * @param boolean $value
     * @return $this
     */
    public function setIsReadonly($value)
    {
        $this->_isReadonly = (bool)$value;
        return $this;
    }
}
