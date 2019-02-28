<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PageBuilder\Plugin\Filter;

use Magento\Widget\Model\Template\Filter as TemplateFilter;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoAppArea frontend
 */
class TemplatePluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var TemplateFilter
     */
    private $templateFilter;

    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->templateFilter = $this->objectManager->get(TemplateFilter::class);
    }

    /**
     * @param string $preFiltered
     * @param string $postFiltered
     * @param string $preFilteredBasename
     * @dataProvider filterDataProvider
     */
    public function testFiltering(string $preFiltered, string $postFiltered, string $preFilteredBasename)
    {
        $this->assertEquals(
            $postFiltered,
            $this->templateFilter->filter($preFiltered),
            "Failed asserting that two strings are equal after filtering $preFilteredBasename"
        );
    }

    /**
     * @return array
     */
    public function filterDataProvider(): array
    {
        $preFilteredFiles = glob(__DIR__ . '/../../_files/template_plugin/*pre_filter*');

        $dataProviderArgs = [];

        foreach ($preFilteredFiles as $preFilteredFile) {
            $preFilteredBasename = basename($preFilteredFile);
            $postFilteredFile = pathinfo($preFilteredFile, PATHINFO_DIRNAME) . '/' . str_replace(
                'pre_filter',
                'post_filter',
                $preFilteredBasename
            );

            $dataProviderArgs[] = [
                file_get_contents($preFilteredFile),
                file_get_contents($postFilteredFile),
                $preFilteredBasename
            ];
        }

        return $dataProviderArgs;
    }
}
