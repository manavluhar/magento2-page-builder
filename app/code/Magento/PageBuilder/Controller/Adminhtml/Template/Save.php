<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PageBuilder\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\PageBuilder\Api\Data\TemplateInterface;
use Magento\PageBuilder\Api\TemplateRepositoryInterface;
use Magento\PageBuilder\Model\TemplateFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Api\ImageContentValidator;
use Magento\Framework\Api\ImageContent;
use Magento\Framework\Api\ImageContentFactory;
use Magento\Framework\Api\FilterBuilder;

/**
 * Save a template within template manager
 */
class Save extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Magento_Backend::content';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TemplateFactory
     */
    private $templateFactory;

    /**
     * @var TemplateRepositoryInterface
     */
    private $templateRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ImageContentValidator
     */
    private $imageContentValidator;

    /**
     * @var ImageContentFactory
     */
    private $imageContentFactory;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param TemplateFactory $templateFactory
     * @param TemplateRepositoryInterface $templateRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Filesystem $filesystem
     * @param ImageContentValidator $imageContentValidator
     * @param ImageContentFactory $imageContentFactory
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        TemplateFactory $templateFactory,
        TemplateRepositoryInterface $templateRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Filesystem $filesystem,
        ImageContentValidator $imageContentValidator,
        ImageContentFactory $imageContentFactory
    ) {
        parent::__construct($context);

        $this->logger = $logger;
        $this->templateFactory = $templateFactory;
        $this->templateRepository = $templateRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filesystem = $filesystem;
        $this->imageContentValidator = $imageContentValidator;
        $this->imageContentFactory = $imageContentFactory;
    }

    /**
     * Save a template to the database
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $request = $this->getRequest();

        // If we're missing required data return an error
        if (!$request->getParam(TemplateInterface::KEY_NAME)
            || !$request->getParam(TemplateInterface::KEY_TEMPLATE)
        ) {
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData(
                [
                    'status' => 'error',
                    'message' => __('A required field is missing.')
                ]
            );
        }

        // Verify a template of the same name does not already exist
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(TemplateInterface::KEY_NAME, $request->getParam(TemplateInterface::KEY_NAME))
                ->create();
            $results = $this->templateRepository->getList($searchCriteria);
            if ($results->getTotalCount() > 0) {
                return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData(
                    [
                        'status' => 'error',
                        'message' => __('A template with this name already exists.')
                    ]
                );
            }
        } catch (LocalizedException $e) {
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData(
                [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]
            );
        }

        $template = $this->templateFactory->create();
        $template->setName($request->getParam(TemplateInterface::KEY_NAME));
        $template->setTemplate($request->getParam(TemplateInterface::KEY_TEMPLATE));
        if ($request->getParam('createdFor')) {
            $template->setCreatedFor($request->getParam('createdFor'));
        }

        // If an upload image is provided let's create the image
        if ($request->getParam('previewImage')) {
            try {
                $mediaDir = $this->filesystem
                    ->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
                $fileName = str_replace(
                    ' ',
                    '-',
                    strtolower($request->getParam(TemplateInterface::KEY_NAME))
                ) . uniqid() . '.jpg';
                $filePath = 'template-manager' . DIRECTORY_SEPARATOR . $fileName;

                // Prepare the image data
                $imgData = str_replace(' ', '+', $request->getParam('previewImage'));
                $imgData = substr($imgData, strpos($imgData, ",") + 1);
                // phpcs:ignore
                $decodedImage = base64_decode($imgData);

                $imageProperties = getimagesizefromstring($decodedImage);
                if (!$imageProperties) {
                    throw new LocalizedException(__('Unable to get properties from image.'));
                }

                /* @var ImageContent $imageContent */
                $imageContent = $this->imageContentFactory->create();
                $imageContent->setBase64EncodedData($imgData);
                $imageContent->setName($fileName);
                $imageContent->setType($imageProperties['mime']);

                if ($this->imageContentValidator->isValid($imageContent)) {
                    // Write the file to the directory
                    $mediaDir->writeFile(
                        $filePath,
                        $decodedImage
                    );

                    // Store the preview image within the new entity
                    $template->setPreviewImage($filePath);
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);

                return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData(
                    [
                        'status' => 'error',
                        'message' => __('Unable to upload image.')
                    ]
                );
            }
        }

        try {
            $this->templateRepository->save($template);
        } catch (LocalizedException $e) {
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData(
                [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]
            );
        } catch (\Exception $e) {
            $this->logger->critical($e);

            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData(
                [
                    'status' => 'error'
                ]
            );
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData(
            [
                'status' => 'ok',
                'message' => __('Template was successfully saved.'),
                'data' => $template->toArray()
            ]
        );
    }
}
