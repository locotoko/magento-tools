<?php

declare(strict_types=1);

namespace Oporteo\Dev\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index implements ActionInterface
{
    public function __construct(
        private readonly PageFactory $pageFactory,
    ) {
    }

    public function execute(): Page
    {
        return $this->pageFactory->create();
    }
}
