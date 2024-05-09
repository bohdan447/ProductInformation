<?php
namespace Perspective\ProductInformation\ViewModule;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;

class ProductsList implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    protected $productRepository;
    protected $configurableProduct;
    protected $searchCriteriaBuilder;
    protected $pricingHelper;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        Configurable $configurableProduct,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PricingHelper $pricingHelper
    ) {
        $this->productRepository = $productRepository;
        $this->configurableProduct = $configurableProduct;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->pricingHelper = $pricingHelper;
    }

    public function getFilteredProducts()
    {
        $categoryId = 23;

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('type_id', Configurable::TYPE_CODE)
            ->addFilter('category_id', $categoryId)
            ->create();

        $products = $this->productRepository->getList($searchCriteria)->getItems();

        $filteredProducts = [];

        foreach ($products as $product) {
            $price = $this->getPriceRange($product);
            if ($price) {
                $filteredProducts[] = [
                    'name' => $product->getName(),
                    'sku' => $product->getSku(),
                    'price' => $price
                ];
            }
        }

        return $filteredProducts;
    }

    protected function getPriceRange($product)
    {
        $childProductPrice = [];
        $childProducts = $this->configurableProduct->getUsedProducts($product);
        foreach ($childProducts as $child) {
            $price = number_format($child->getPrice(), 2, '.', '');
            $finalPrice = number_format($child->getFinalPrice(), 2, '.', '');
            if ($price == $finalPrice) {
                $childProductPrice[] = $price;
            } else if ($finalPrice < $price) {
                $childProductPrice[] = $finalPrice;
            }
        }

        $max = max($childProductPrice);
        $min = min($childProductPrice);

        if ($min >= 50 && $max <= 60) {
            return $this->pricingHelper->currencyByStore($min) . '-' . $this->pricingHelper->currencyByStore($max);
        } else {
            return null;
        }
    }
}
