<?php
namespace SR\SalesProductGrid\Plugin\Sales\Model\ResourceModel\Order\Grid;

class Collection
{
    /**
     * @var \SR\SalesProductGrid\Model\Sales\TemporaryStorageFactory
     */
    protected $temporaryStorageFactory;

    public function __construct(
        \SR\SalesProductGrid\Model\Sales\TemporaryStorageFactory $temporaryStorageFactory
    ) {
        $this->temporaryStorageFactory = $temporaryStorageFactory;
    }

    public function beforeLoad(
        \Magento\Sales\Model\ResourceModel\Order\Grid\Collection $subject
    ) {
        $temporaryStorage = $this->temporaryStorageFactory->create();
        $tempCollection = $subject->getConnection()->select()->from('sales_order_item')
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(
                [
                    'order_id',
                    'products_name' => new \Zend_Db_Expr('GROUP_CONCAT(`sales_order_item`.name SEPARATOR "|")'),
                    'products_sku' => new \Zend_Db_Expr('GROUP_CONCAT(`sales_order_item`.sku SEPARATOR "|")'),
                ]
            )->where('parent_item_id IS NULL')
            ->group('order_id');
        $table= $temporaryStorage->storeOrderItem($subject->getConnection()->fetchAll($tempCollection));
        $subject->getSelect()->joinInner(
            [
                'temp_table' => $table->getName(),
            ],
            'main_table.entity_id = temp_table.' . \SR\SalesProductGrid\Model\Sales\TemporaryStorage::FIELD_ORDER_ID,
            ['products_name', 'products_sku']
        );
    }
}