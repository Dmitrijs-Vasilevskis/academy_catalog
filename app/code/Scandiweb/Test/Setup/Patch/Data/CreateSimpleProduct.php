<?php

declare(strict_types=1);

namespace Scandiweb\Test\Setup\Patch\Data;

use Magento\Catalog\Api\CategoryLinkManagementInterface as CategoryLink;
use Magento\Catalog\Api\Data\ProductInterfaceFactory as ProductFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollection;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\State;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

class CreateSimpleProduct implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var EavSetup
     */
    protected $eavSetup;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var State
     */
    protected State $appState;

    /**
     * @var CategoryLink
     */
    protected $categoryLink;

    /**
     * @var CategoryCollection
     */
    protected $categoryCollection;

    /**
     * @var SourceItemInterfaceFactory
     */
    protected $sourceItemFactory;

    /**
     * @var SourceItemsSaveInterface
     */
    protected $sourceItemsSaveInterface;

    protected array $sourceItems = [];

    /**
     * @param ModuleDataSetupInterface   $moduleDataSetup
     * @param ProductFactory             $productFactory
     * @param State                      $appState
     * @param EavSetup                   $eavSetup
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryLink               $categoryLink
     * @param CategoryCollection         $categoryCollection
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param SourceItemsSaveInterface   $sourceItemsSaveInterface
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ProductFactory $productFactory,
        State $appState,
        EavSetup $eavSetup,
        ProductRepositoryInterface $productRepository,
        CategoryLink $categoryLink,
        CategoryCollection $categoryCollection,
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->productFactory = $productFactory;
        $this->appState = $appState;
        $this->eavSetup = $eavSetup;
        $this->productRepository = $productRepository;
        $this->categoryLink = $categoryLink;
        $this->categoryCollection = $categoryCollection;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
    }

    public function apply()
    {
        $this->appState->emulateAreaCode('adminhtml', [$this, 'execute']);
    }

    public function execute()
    {
        $this->moduleDataSetup->startSetup();
        // create the product
        $product = $this->productFactory->create();

        if ($product->getIdBySku('test-product')) {
            return;
        }

        $attributeSetId = $this->eavSetup->getAttributeSetId(Product::ENTITY, 'Default');

        // set default attributes
        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId($attributeSetId)
            ->setName('Test product')
            ->setSku('test-product')
            ->setUrlKey('testProduct')
            ->setPrice(7.77)
            ->setVisibility(Visibility::VISIBILITY_BOTH);

        // save the product to the repository
        $this->productRepository->save($product);

        // set the product to category
        $categoryTitle = ['Men'];
        $categoryId = $this->categoryCollection->create()
            ->addAttributeToFilter('name', ['in' => $categoryTitle])
            ->getAllIds();

        $this->categoryLink->assignProductToCategories($product->getSku(), $categoryId);

        // create a source item
        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceCode('default');
        $sourceItem->setQuantity(100);
        $sourceItem->setSku($product->getSku());
        $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);
        $this->sourceItems[] = $sourceItem;

        // save the source item
        $this->sourceItemsSaveInterface->execute($this->sourceItems);

        $this->moduleDataSetup->endSetup();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
