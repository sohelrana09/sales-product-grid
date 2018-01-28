<?php
namespace SR\SalesProductGrid\Model\Sales;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;

class TemporaryStorage
{
    const TEMPORARY_TABLE_PREFIX = 'order_tmp_';

    const FIELD_ENTITY_ID = 'entity_id';
    const FIELD_ORDER_ID = 'order_id';
    const FIELD_PRODUCTS_NAME = 'products_name';
    const FIELD_PRODUCTS_SKU = 'products_sku';

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(\Magento\Framework\App\ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function storeOrderItem($orders)
    {
        $data = [];
        $increment = 1;
        foreach ($orders as $order) {
            $data[] = [
                $increment++,
                $order['order_id'],
                $order['products_name'],
                $order['products_sku'],
            ];
        }

        return $this->populateTemporaryTable($this->createTemporaryTable(), $data);
    }

    /**
     * Populates temporary table
     *
     * @param Table $table
     * @param array $data
     * @return Table
     * @throws \Zend_Db_Exception
     */
    private function populateTemporaryTable(Table $table, $data)
    {
        if (count($data)) {
            $this->getConnection()->insertArray(
                $table->getName(),
                [
                    self::FIELD_ENTITY_ID,
                    self::FIELD_ORDER_ID,
                    self::FIELD_PRODUCTS_NAME,
                    self::FIELD_PRODUCTS_SKU,
                ],
                $data
            );
        }
        return $table;
    }

    /**
     * @return false|AdapterInterface
     */
    private function getConnection()
    {
        return $this->resource->getConnection();
    }

    /**
     * @return Table
     * @throws \Zend_Db_Exception
     */
    private function createTemporaryTable()
    {
        $connection = $this->getConnection();
        $tableName = $this->resource->getTableName(str_replace('.', '_', uniqid(self::TEMPORARY_TABLE_PREFIX, true)));
        $table = $connection->newTable($tableName);
        $connection->dropTemporaryTable($table->getName());
        $table->addColumn(
            self::FIELD_ENTITY_ID,
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity ID'
        );
        $table->addColumn(
            self::FIELD_ORDER_ID,
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'nullable' => false],
            'Order ID'
        );
        $table->addColumn(
            self::FIELD_PRODUCTS_NAME,
            Table::TYPE_TEXT,
            255,
            ['unsigned' => true, 'nullable' => false],
            'Products Name'
        );
        $table->addColumn(
            self::FIELD_PRODUCTS_SKU,
            Table::TYPE_TEXT,
            255,
            ['unsigned' => true, 'nullable' => false],
            'Products Sku'
        );
        $table->setOption('type', 'memory');
        $connection->createTemporaryTable($table);
        return $table;
    }
}