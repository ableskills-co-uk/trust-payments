<?php

namespace SecureTrading\Trust\Ui\Component\Listing\Column;

use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \SecureTrading\Trust\Model\ResourceModel\Subscription\CollectionFactory as SubsCollectionFactory;
use Magento\Framework\UrlInterface;

/**
 * Class Subscription
 *
 * @package SecureTrading\Trust\Ui\Component\Listing\Column
 */
class Subscription extends Column
{
	/**
	 * @var OrderRepositoryInterface
	 */
	protected $_orderRepository;

	/**
	 * @var SearchCriteriaBuilder
	 */
	protected $_searchCriteria;

	/**
	 * @var SubsCollectionFactory
	 */
	protected $collectionFactory;

	/**
	 * @var UrlInterface
	 */
	protected $urlBuilder;

	/**
	 * Subscription constructor.
	 *
	 * @param ContextInterface $context
	 * @param UiComponentFactory $uiComponentFactory
	 * @param OrderRepositoryInterface $orderRepository
	 * @param SearchCriteriaBuilder $criteria
	 * @param SubsCollectionFactory $collectionFactory
	 * @param UrlInterface $urlBuilder
	 * @param array $components
	 * @param array $data
	 */
	function __construct(ContextInterface $context, UiComponentFactory $uiComponentFactory, OrderRepositoryInterface $orderRepository, SearchCriteriaBuilder $criteria, SubsCollectionFactory $collectionFactory, UrlInterface $urlBuilder, array $components = [], array $data = [])
    {
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria = $criteria;
        $this->collectionFactory = $collectionFactory;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

	/**
	 * @param array $dataSource
	 * @return array
	 */
	function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
	            $id = '';
                $order = $this->_orderRepository->get($item["entity_id"]);
                $order_id = $order->getIncrementId();
                $subsParentModel = $this->collectionFactory->create()->addFieldToFilter('order_id',$order_id)->getFirstItem()->getData('parent_order_id');
                $subsModels = $this->collectionFactory->create()->addFieldToFilter('parent_order_id',$subsParentModel);
                foreach ($subsModels as $subsModel){
                	$orderId = $subsModel->getData('parent_order_id');
                	$orderParentId = $subsModel->getData('order_id');
                	if($orderId == $orderParentId){
		                $id = $subsModel->getData('id');
		                $order_id = $orderParentId;
		                break;
	                }
                }
                $viewUrlPath = $this->getData('config/viewUrlPath') ?: '#';
                $urlEntityParamName = $this->getData('config/urlEntityParamName') ?: 'entity_id';
                $item[$this->getData('name')] = [
	                $id => [
                        'href' => $this->urlBuilder->getUrl(
                            $viewUrlPath,
                            [
                                $urlEntityParamName => $order_id
                            ]
                        ),
                        'label' => __($id)
                    ]
                ];
            }
        }

        return $dataSource;
    }
}