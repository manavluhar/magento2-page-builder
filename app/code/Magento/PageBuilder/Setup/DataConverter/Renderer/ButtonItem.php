<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PageBuilder\Setup\DataConverter\Renderer;

use Magento\PageBuilder\Setup\DataConverter\RendererInterface;
use Magento\PageBuilder\Setup\DataConverter\EavAttributeLoaderInterface;
use Magento\PageBuilder\Setup\DataConverter\StyleExtractorInterface;

/**
 * Render button item to PageBuilder format
 */
class ButtonItem implements RendererInterface
{
    /**
     * @var StyleExtractorInterface
     */
    private $styleExtractor;

    /**
     * @var EavAttributeLoaderInterface
     */
    private $eavAttributeLoader;

    public function __construct(
        StyleExtractorInterface $styleExtractor,
        EavAttributeLoaderInterface $eavAttributeLoader
    ) {
        $this->styleExtractor = $styleExtractor;
        $this->eavAttributeLoader = $eavAttributeLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function render(array $itemData, array $additionalData = [])
    {
        if (!isset($itemData['entityId'])) {
            throw new \InvalidArgumentException('entityId is missing.');
        }
        $eavData = $this->eavAttributeLoader->load($itemData['entityId']);

        $cssClasses = $eavData['css_classes'] ?? '';

        $rootElementAttributes = [
            'data-role' => 'button-item',
            'style' => 'display: inline-block;',
            'class' => $cssClasses
        ];

        $buttonStyleAttribute = '';
        if (isset($itemData['formData'])) {
            $styleAttributeValue = $this->styleExtractor->extractStyle($itemData['formData']);
            if ($styleAttributeValue) {
                $buttonStyleAttribute = ' style="' . $styleAttributeValue . '"';
            }
        }

        $rootElementHtml = '<div';
        foreach ($rootElementAttributes as $attributeName => $attributeValue) {
            $rootElementHtml .= $attributeValue ? " $attributeName=\"$attributeValue\"" : '';
        }

        $rootElementHtml .= '><a href="'
            . ($eavData['link_url'] ?? '')
            . $buttonStyleAttribute
            . ' class="pagebuilder-button-primary"><span>'
            . ($eavData['link_text'] ?? '')
            . '</span></a></div>';

        return $rootElementHtml;
    }
}
