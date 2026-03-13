<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Causal\ImageAutoresize\Controller;

use Causal\ImageAutoresize\Event\ProcessConfigurationEvent;
use Causal\ImageAutoresize\Event\ProcessDefaultConfigurationEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Form\FormAction;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;

if ((new Typo3Version())->getMajorVersion() >= 14) {
    require_once __DIR__ . '/V14/AbstractConfigurationController.php';
    abstract class AbstractConfigurationController extends \Causal\ImageAutoresize\Controller\V14\AbstractConfigurationController
    {
    }
} else {
    require_once __DIR__ . '/V12/AbstractConfigurationController.php';
    abstract class AbstractConfigurationController extends \Causal\ImageAutoresize\Controller\V12\AbstractConfigurationController
    {
    }
}

/**
 * Configuration controller.
 *
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */
class ConfigurationController extends AbstractConfigurationController
{
    const virtualTable = 'tx_imageautoresize';
    const virtualRecordId = 1;

    /**
     * @var \TYPO3\CMS\Backend\Template\ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Generally used for accumulating the output content of backend modules
     *
     * @var string
     */
    public string $content = '';

    /**
     * @var string
     */
    protected string $retUrl = '';

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $typo3Version = (new Typo3Version())->getMajorVersion();
        $moduleTemplateFactory = GeneralUtility::makeInstance(ModuleTemplateFactory::class);
        $this->moduleTemplate = $moduleTemplateFactory->create($request);
        if ($typo3Version >= 14) {
            $this->retUrl = $this->resolveReturnUrl();

            // Close document if a request for closing the document has been sent
            $requestAction = FormAction::createFromRequest($request);
            if ($requestAction->shouldHandleDocumentClosing()) {
                return new RedirectResponse((string)$this->retUrl, 303);
            }

            // Process incoming data via DataHandler?
            if ($requestAction->shouldProcessData()) {
                $this->processData($request);
                // Redirect if element should be closed after save
                if ($requestAction->shouldCloseAfterSave()) {
                    return new RedirectResponse((string)$this->retUrl, 303);
                }
            }
        } else {
            $this->processData($request);
        }

        $formTag = '<form action="" method="post" name="editform" id="EditDocumentController">';

        $this->moduleTemplate->setForm($formTag);

        $this->content .= sprintf('<h3>%s</h3>', htmlspecialchars($this->sL('title')));
        $this->addStatisticsAndSocialLink();

        // Generate the content
        $this->moduleContent($request, $this->config);

        // Compile document
        $this->addToolbarButtons();

        $this->moduleTemplate->assign('content', $this->content);
        return $this->moduleTemplate->renderResponse('Configuration');
    }

    /**
     * Creates the toolbar buttons.
     */
    protected function addToolbarButtons(): void
    {
        // Render SAVE type buttons:
        // The action of each button is decided by its name attribute. (See doProcessData())
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $saveSplitButton = $buttonBar->makeSplitButton();

        $locallangCore = 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf';
        $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);

        $iconSize = $this->typo3Version >= 13
            ? \TYPO3\CMS\Core\Imaging\IconSize::SMALL
            : \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL;

        // SAVE button:
        $saveButton = $buttonBar->makeInputButton()
            ->setTitle(htmlspecialchars($this->languageService->sL($locallangCore . ':rm.saveDoc')))
            ->setName('_savedok')
            ->setValue('1')
            ->setForm('EditDocumentController')
            ->setIcon($iconFactory->getIcon(
                'actions-document-save',
                $iconSize
            ));
        $saveSplitButton->addItem($saveButton, true);

        // SAVE & CLOSE button:
        $saveAndCloseButton = $buttonBar->makeInputButton()
            ->setTitle(htmlspecialchars($this->languageService->sL($locallangCore . ':rm.saveCloseDoc')))
            ->setName('_saveandclosedok')
            ->setValue('1')
            ->setForm('EditDocumentController')
            ->setClasses('t3js-editform-submitButton')
            ->setIcon($iconFactory->getIcon(
                'actions-document-save-close',
                $iconSize
            ));
        $saveSplitButton->addItem($saveAndCloseButton);

        $buttonBar->addButton($saveSplitButton, ButtonBar::BUTTON_POSITION_LEFT, 2);

        // CLOSE button:
        $closeButton = $buttonBar->makeLinkButton()
            ->setTitle(htmlspecialchars($this->languageService->sL($locallangCore . ':rm.closeDoc')))
            ->setHref('#')
            ->setClasses('t3js-editform-close')
            ->setIcon($iconFactory->getIcon(
                'actions-view-go-back',
                $iconSize
            ));
        $buttonBar->addButton($closeButton);
    }

    /**
     * Returns the default configuration.
     *
     * @return array
     */
    protected static function getDefaultConfiguration(): array
    {
        return [
            'directories' => '1:/',
            'file_types' => 'jpg,jpeg,png',
            'threshold' => '400K',
            'max_width' => '1920',
            'max_height' => '1080',
            'max_size' => '100M',
            'auto_orient' => true,
            'conversion_mapping' => implode(',', [
                'ai => jpg',
                'bmp => jpg',
                'pcx => jpg',
                'tga => jpg',
                'tif => jpg',
                'tiff => jpg',
            ]),
        ];
    }

    /**
     * Processes submitted data and stores it to localconf.php.
     *
     * @param ServerRequestInterface $request
     * @return void
     */
    protected function processData(ServerRequestInterface $request): void
    {
        if ($this->typo3Version >= 14) {
            $close = false;
            $save = true;
            $saveAndClose = false;
        } else {
            $close = (bool)($request->getParsedBody()['closeDoc'] ?? false);
            $save = (bool)($request->getParsedBody()['doSave'] ?? false);
            $saveAndClose = (bool)($request->getParsedBody()['_saveandclosedok'] ?? false);
        }

        if ($save || $saveAndClose) {
            $table = static::virtualTable;
            $id = static::virtualRecordId;
            $field = 'rulesets';

            $inputData_tmp = $request->getParsedBody()['data'];
            $data = $inputData_tmp[$table][$id];

            if (count($inputData_tmp[$table]) > 1) {
                foreach ($inputData_tmp[$table] as $key => $values) {
                    if ($key === $id) continue;
                    ArrayUtility::mergeRecursiveWithOverrule($data, $values);
                }
            }

            $newConfig = $this->config;
            ArrayUtility::mergeRecursiveWithOverrule($newConfig, $data);

            // Action commands (sorting order and removals of FlexForm elements)
            $ffValue = &$data[$field];
            if ($ffValue) {
                // Remove FlexForm elements if needed
                foreach ($ffValue['data']['sDEF']['lDEF']['ruleset']['el'] ?? [] as $key => $value) {
                    if (($value['_ACTION'] ?? '') === 'DELETE') {
                        unset($ffValue['data']['sDEF']['lDEF']['ruleset']['el'][$key]);
                    }
                    unset($ffValue['data']['sDEF']['lDEF']['ruleset']['el'][$key]['_ACTION']);
                }
                // Renumber all FlexForm temporary ids
                $this->persistFlexForm($ffValue['data']);

                // Keep order of FlexForm elements
                $newConfig[$field] = $ffValue;
            }

            // Persist configuration
            $localconfConfig = $newConfig;
            $localconfConfig['conversion_mapping'] = implode(',', GeneralUtility::trimExplode(LF, $localconfConfig['conversion_mapping'], true));

            if ($this->persistConfiguration($localconfConfig)) {
                $this->config = $newConfig;
            }
        }

        if ($close || $saveAndClose) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            if ($this->typo3Version >= 14) {
                $closeUrl = $this->retUrl;
            } else {
                $closeUrl = (string)$uriBuilder->buildUriFromRoute('tools_ExtensionmanagerExtensionmanager');
            }
            throw new PropagateResponseException(new RedirectResponse($closeUrl, 303), 1666353555);
        }
    }

    /**
     * @return array
     */
    public static function readConfiguration(): array
    {
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $configurationFileName = static::getConfigurationFileName();

        $configuration = is_file($configurationFileName) ? include($configurationFileName) : [];
        if (!is_array($configuration) || empty($configuration)) {
            $configuration = static::getDefaultConfiguration();
            $configuration = $eventDispatcher->dispatch(new ProcessDefaultConfigurationEvent($configuration))->getConfiguration();
        }

        $configuration = $eventDispatcher->dispatch(new ProcessConfigurationEvent($configuration))->getConfiguration();

        return $configuration;
    }

    /**
     * Writes configuration to image_autoresize.config.php.
     *
     * @param array $config
     * @return bool
     */
    protected function persistConfiguration(array $config): bool
    {
        $configurationFileName = static::getConfigurationFileName();

        $exportConfig = var_export($config, true);
        $exportConfig = str_replace('array (', '[', $exportConfig);
        if (substr($exportConfig, -1) === ')') {
            $exportConfig = substr($exportConfig, 0, strlen($exportConfig) - 1) . ']';
        }
        $exportConfig = preg_replace('/=>\\s*[[]/s', '=> [', $exportConfig);
        $lines = explode(LF, $exportConfig);
        foreach ($lines as $i => $line) {
            if (preg_match('/^(\\s+)(.+)$/', $line, $matches)) {
                if ($matches[2] === '),') {
                    // Convert ending of former array declaration to new syntax
                    $matches[2] = '],';
                }
                $lines[$i] = str_repeat(' ', 2 * strlen($matches[1])) . $matches[2];
            }
        }
        $exportConfig = implode(LF, $lines);

        $content = '<?' . 'php' . LF . 'return ' . $exportConfig . ';' . LF;
        $success = GeneralUtility::writeFile($configurationFileName, $content);
        return true;
    }

    /**
     * Loads the configuration of the virtual table 'tx_imageautoresize'.
     */
    protected function loadVirtualTca(): void
    {
        $GLOBALS['TCA'][static::virtualTable] = include(GeneralUtility::getFileAbsFileName('EXT:image_autoresize/Configuration/TCA/Module/Options.php'));
        if ($this->typo3Version >= 14) {
            $tcaSchemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
            $tcaSchemaFactory->rebuild($GLOBALS['TCA']);
        }
    }

    /**
     * Persists FlexForm items by removing 'ID-' in front of new
     * items.
     *
     * @param array &$valueArray : by reference
     */
    protected function persistFlexForm(array &$valueArray): void
    {
        foreach ($valueArray as $key => $value) {
            if ($key === 'el') {
                foreach ($value as $idx => $v) {
                    if ($v && substr($idx, 0, 3) === 'ID-') {
                        $valueArray[$key][substr($idx, 3)] = $v;
                        unset($valueArray[$key][$idx]);
                    }
                }
            } elseif (isset($valueArray[$key])) {
                $this->persistFlexForm($valueArray[$key]);
            }
        }
    }

    /**
     * Returns some statistics and a social link to Twitter.
     */
    protected function addStatisticsAndSocialLink(): void
    {
        $fileName = Environment::getPublicPath() . '/typo3temp/.tx_imageautoresize';

        if (!is_file($fileName)) {
            return;
        }

        $data = json_decode(file_get_contents($fileName), true);
        if (!is_array($data) || !(isset($data['images']) && isset($data['bytes']))) {
            return;
        }

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:image_autoresize/Resources/Public/Css/twitter.css');

        $totalSpaceClaimed = GeneralUtility::formatSize((int)$data['bytes']);
        $messagePattern = $this->sL('storage.claimed');
        $message = sprintf($messagePattern, $totalSpaceClaimed, (int)$data['images']);

        $flashMessage = htmlspecialchars($message);

        $twitterMessagePattern = $this->sL('social.twitter');
        $message = sprintf($twitterMessagePattern, $totalSpaceClaimed);
        $url = 'https://extensions.typo3.org/extension/image_autoresize/';

        $twitterLink = 'https://x.com/intent/tweet?text=' . urlencode($message) . '&url=' . urlencode($url);
        $flashMessage .= '
            <div class="custom-tweet-button">
                <a href="' . $twitterLink . '" title="' . htmlspecialchars($this->sL('social.share')) . '" target="_blank" class="btn">
                    <i></i>
                    <span class="label">Post</span>
                </a>
            </div>';

        $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
        $iconSize = $this->typo3Version >= 13
            ? \TYPO3\CMS\Core\Imaging\IconSize::SMALL
            : \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL;
        $icon = $iconFactory->getIcon(
            'actions-info',
            $iconSize
        );
        $this->content .= '
                <div class="alert alert-info">
                    <div class="media">
                        <div class="media-left">
                            <span class="icon-emphasized"> ' . $icon . '</span>
                        </div>
                        <div class="media-body">
                            ' . $flashMessage . '
                        </div>
                    </div>
                </div>
            ';
    }

    /**
     * Returns the absolute path to the configuration file.
     *
     * @return string
     */
    protected static function getConfigurationFileName(): string
    {
        return Environment::getConfigPath() . '/image_autoresize.config.php';
    }

    protected function sL(string $key): string
    {
        $input = 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_mod.xlf:' . $key;
        return $this->languageService->sL($input);
    }

    protected function resolveReturnUrl(): string
    {
        $module = $this->moduleProvider->getModule('extensionmanager', $this->getBackendUser());
        $routeName = $module ? $module->getIdentifier() : 'dummy';
        return (string)$this->uriBuilder->buildUriFromRoute($routeName);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}

// ReflectionMethod does not work properly with arguments passed as reference thus
// using a trick here
class CustomDataHandler extends \TYPO3\CMS\Core\DataHandling\DataHandler
{

    /**
     * Actions for flex form element (move, delete)
     * allows to remove and move flexform sections
     *
     * @param array &$valueArray by reference
     * @param array $actionCMDs
     * @return void
     */
    public function _ACTION_FLEX_FORMdata(&$valueArray, $actionCMDs)
    {
        parent::_ACTION_FLEX_FORMdata($valueArray, $actionCMDs);
    }

}
