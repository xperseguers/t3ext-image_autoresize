<?php
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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Configuration controller.
 *
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class ConfigurationController
{

    const virtualTable = 'tx_imageautoresize';
    const virtualRecordId = 1;

    /**
     * @var string
     */
    protected $extKey = 'image_autoresize';

    /**
     * @var array
     */
    protected $expertKey = 'image_autoresize_ff';

    /**
     * @var \TYPO3\CMS\Lang\LanguageService
     */
    protected $languageService;

    /**
     * @var \TYPO3\CMS\Backend\Form\FormEngine
     */
    protected $tceforms;

    /**
     * @var \TYPO3\CMS\Backend\Form\FormResultCompiler $formResultCompiler
     */
    protected $formResultCompiler;

    /**
     * @var \TYPO3\CMS\Backend\Template\ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var array
     */
    protected $config;

    /**
     * Generally used for accumulating the output content of backend modules
     *
     * @var string
     */
    public $content = '';

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\ModuleTemplate::class);
        $this->languageService = $GLOBALS['LANG'];

        $config = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->expertKey];
        $this->config = $config ? unserialize($config) : $this->getDefaultConfiguration();
        $this->config['conversion_mapping'] = implode(LF, explode(',', $this->config['conversion_mapping']));
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->languageService->includeLLFile('EXT:image_autoresize/Resources/Private/Language/locallang_mod.xlf');
        $this->processData();

        $formTag = '<form action="" method="post" name="editform">';

        $this->moduleTemplate->setForm($formTag);

        $this->content .= sprintf('<h3>%s</h3>', $this->languageService->getLL('title', true));
        $this->addStatisticsAndSocialLink();

        $row = $this->config;
        if (version_compare(TYPO3_version, '7.6.17', '<')) {
            $this->fixRecordForFormEngine($row, ['file_types', 'usergroup']);
        }
        $this->moduleContent($row);

        // Compile document
        $this->addToolbarButtons();
        $this->moduleTemplate->setContent($this->content);
        $content = $this->moduleTemplate->renderContent();

        $response->getBody()->write($content);

        return $response;
    }

    /**
     * FormEngine now expects an array of data instead of a comma-separated list of
     * values for select fields. This method ensures the corresponding fields in $row
     * are of the expected type and "fix" them if needed.
     *
     * @param array &$row
     * @param array $tcaSelectFields
     * @return void
     */
    protected function fixRecordForFormEngine(array &$row, array $tcaSelectFields)
    {
        foreach ($tcaSelectFields as $tcaField) {
            if (isset($row[$tcaField])) {
                $row[$tcaField] = GeneralUtility::trimExplode(',', $row[$tcaField], true);
            }
        }
        if (isset($row['rulesets']['data']['sDEF']['lDEF']['ruleset']['el'])) {
            foreach ($row['rulesets']['data']['sDEF']['lDEF']['ruleset']['el'] as &$el) {
                foreach ($tcaSelectFields as $tcaField) {
                    if (isset($el['container']['el'][$tcaField]['vDEF'])) {
                        $el['container']['el'][$tcaField]['vDEF'] = GeneralUtility::trimExplode(',', $el['container']['el'][$tcaField]['vDEF'], true);
                    }
                }
            }
        }
    }

    /**
     * Generates the module content.
     *
     * @param array $row
     * @return void
     */
    protected function moduleContent(array $row)
    {
        $this->formResultCompiler = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\FormResultCompiler::class);

        $wizard = $this->formResultCompiler->JStop();
        $wizard .= $this->buildForm($row);
        $wizard .= $this->formResultCompiler->printNeededJSFunctions();

        $this->content .= $wizard;
    }

    /**
     * Builds the expert configuration form.
     *
     * @param array $row
     * @return string
     */
    protected function buildForm(array $row)
    {
        $record = [
            'uid' => static::virtualRecordId,
            'pid' => 0,
        ];
        $record = array_merge($record, $row);

        // Trick to use a virtual record
        $dataProviders =& $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'];

        // Recent version of TYPO3 is since 7.6.17 for TYPO3 v7 and > 8.6.1 for TYPO3 v8
        $isRecentV7OrV8 = version_compare(TYPO3_version, '8.6.1', '>')
            || (version_compare(TYPO3_version, '7.6.17', '>=') && version_compare(TYPO3_version, '8.0', '<'));
        if ($isRecentV7OrV8) {
            $dataProviders[\Causal\ImageAutoresize\Backend\Form\FormDataProvider\VirtualDatabaseEditRow::class] = [
                'before' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class,
                ]
            ];
        } else {
            // Either TYPO3 < 7.6.17 or TYPO3 8.0.0 - 8.6.1
            $originalProvider = \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class;
            $databaseEditRowProvider = $dataProviders[$originalProvider];
            unset($dataProviders[$originalProvider]);
            $dataProviders[\Causal\ImageAutoresize\Backend\Form\FormDataProvider\VirtualDatabaseEditRow::class] = $databaseEditRowProvider;
        }

        // Initialize record in our virtual provider
        \Causal\ImageAutoresize\Backend\Form\FormDataProvider\VirtualDatabaseEditRow::initialize($record);

        /** @var \TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord $formDataGroup */
        $formDataGroup = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord::class);
        /** @var \TYPO3\CMS\Backend\Form\FormDataCompiler $formDataCompiler */
        $formDataCompiler = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\FormDataCompiler::class, $formDataGroup);
        /** @var \TYPO3\CMS\Backend\Form\NodeFactory $nodeFactory */
        $nodeFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\NodeFactory::class);

        $formDataCompilerInput = [
            'tableName' => static::virtualTable,
            'vanillaUid' => $record['uid'],
            'command' => 'edit',
            'returnUrl' => '',
        ];

        // Load the configuration of virtual table 'tx_imageautoresize'
        $this->loadVirtualTca();

        $formData = $formDataCompiler->compile($formDataCompilerInput);
        $formData['renderType'] = 'outerWrapContainer';
        $formResult = $nodeFactory->create($formData)->render();

        // Remove header and footer
        $html = preg_replace('/<h1>.*<\/h1>/', '', $formResult['html']);

        $startFooter = strrpos($html, '<div class="help-block text-right">');
        $endTag = '</div>';

        if ($startFooter !== false) {
            $endFooter = strpos($html, $endTag, $startFooter);
            $html = substr($html, 0, $startFooter) . substr($html, $endFooter + strlen($endTag));
        }

        $formResult['html'] = '';
        $formResult['doSaveFieldName'] = 'doSave';

        // @todo: Put all the stuff into FormEngine as final "compiler" class
        // @todo: This is done here for now to not rewrite JStop()
        // @todo: and printNeededJSFunctions() now
        $this->formResultCompiler->mergeResult($formResult);

        // Combine it all
        $formContent = '
			<!-- EDITING FORM -->
			' . $html . '

			<input type="hidden" name="returnUrl" value="' . htmlspecialchars($this->retUrl) . '" />
			<input type="hidden" name="closeDoc" value="0" />
			<input type="hidden" name="doSave" value="0" />
			<input type="hidden" name="_serialNumber" value="' . md5(microtime()) . '" />
			<input type="hidden" name="_scrollPosition" value="" />';

        if (version_compare(TYPO3_version, '8.6', '>=')) {
            $overriddenAjaxUrl = GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('TxImageAutoresize::record_flex_container_add'));
            $formContent .= <<<HTML
<script type="text/javascript">
    TYPO3.settings.ajaxUrls['record_flex_container_add'] = $overriddenAjaxUrl;
</script>
HTML;
        }

        return $formContent;
    }

    /**
     * Creates the toolbar buttons.
     *
     * @return void
     */
    protected function addToolbarButtons()
    {
        // Render SAVE type buttons:
        // The action of each button is decided by its name attribute. (See doProcessData())
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $saveSplitButton = $buttonBar->makeSplitButton();

        // SAVE button:
        $saveButton = $buttonBar->makeInputButton()
            ->setTitle($this->languageService->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', true))
            ->setName('_savedok')
            ->setValue('1')
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                'actions-document-save',
                \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL
            ));
        $saveSplitButton->addItem($saveButton, true);

        // SAVE & CLOSE button:
        $saveAndCloseButton = $buttonBar->makeInputButton()
            ->setName('_saveandclosedok')
            ->setClasses('t3js-editform-submitButton')
            ->setValue('1')
            ->setTitle($this->languageService->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc', true))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                'actions-document-save-close',
                \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL
            ));
        $saveSplitButton->addItem($saveAndCloseButton);

        $buttonBar->addButton($saveSplitButton, \TYPO3\CMS\Backend\Template\Components\ButtonBar::BUTTON_POSITION_LEFT, 2);

        // CLOSE button:
        $closeButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setClasses('t3js-editform-close')
            ->setTitle($this->languageService->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc', true))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                'actions-view-go-back',
                \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL
            ));
        $buttonBar->addButton($closeButton);
    }

    /**
     * Prints out the module HTML.
     *
     * @return string HTML output
     */
    public function printContent()
    {
        echo $this->content;
    }

    /**
     * Returns the default configuration.
     *
     * @return array
     */
    protected function getDefaultConfiguration()
    {
        return [
            'directories' => 'fileadmin/,uploads/',
            'file_types' => 'jpg,jpeg,png',
            'threshold' => '400K',
            'max_width' => '1024',
            'max_height' => '768',
            'auto_orient' => '1',
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
     * @return void
     */
    protected function processData()
    {
        $close = GeneralUtility::_GP('closeDoc');
        $save = GeneralUtility::_GP('_savedok');
        $saveAndClose = GeneralUtility::_GP('_saveandclosedok');

        if ($save || $saveAndClose) {
            $table = static::virtualTable;
            $id = static::virtualRecordId;
            $field = 'rulesets';

            $inputData_tmp = GeneralUtility::_GP('data');
            $data = $inputData_tmp[$table][$id];

            if (version_compare(TYPO3_version, '8.6', '>=')) {
                if (count($inputData_tmp[$table]) > 1) {
                    foreach ($inputData_tmp[$table] as $key => $values) {
                        if ($key === $id) continue;
                        ArrayUtility::mergeRecursiveWithOverrule($data, $values);
                    }
                }
            }

            $newConfig = $this->config;
            ArrayUtility::mergeRecursiveWithOverrule($newConfig, $data);

            // Action commands (sorting order and removals of FlexForm elements)
            $ffValue = &$data[$field];
            if ($ffValue) {
                $actionCMDs = GeneralUtility::_GP('_ACTION_FLEX_FORMdata');
                if (is_array($actionCMDs[$table][$id][$field]['data'])) {
                    $dataHandler = new CustomDataHandler();
                    $dataHandler->_ACTION_FLEX_FORMdata($ffValue['data'], $actionCMDs[$table][$id][$field]['data']);
                }
                // Renumber all FlexForm temporary ids
                $this->persistFlexForm($ffValue['data']);

                // Keep order of FlexForm elements
                $newConfig[$field] = $ffValue;
            }

            // Write back configuration to localconf.php
            $localconfConfig = $newConfig;
            $localconfConfig['conversion_mapping'] = implode(',', GeneralUtility::trimExplode(LF, $localconfConfig['conversion_mapping'], true));

            if ($this->writeToLocalconf($this->expertKey, $localconfConfig)) {
                $this->config = $newConfig;
            }
        }

        if ($close || $saveAndClose) {
            $closeUrl = BackendUtility::getModuleUrl('tools_ExtensionmanagerExtensionmanager');
            \TYPO3\CMS\Core\Utility\HttpUtility::redirect($closeUrl);
        }
    }

    /**
     * Writes a configuration line to AdditionalConfiguration.php.
     * We don't use the <code>tx_install</code> methods as they add unneeded
     * comments at the end of the file.
     *
     * @param string $key
     * @param array $config
     * @return boolean
     */
    protected function writeToLocalconf($key, array $config)
    {
        /** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
        $objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        /** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
        $configurationManager = $objectManager->get(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);
        return $configurationManager->setLocalConfigurationValueByPath('EXT/extConf/' . $key, serialize($config));
    }

    /**
     * Initializes <code>\TYPO3\CMS\Backend\Form\FormEngine</code> class for use in this module.
     *
     * @return void
     */
    protected function initTCEForms()
    {
        $this->tceforms = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\FormEngine::class);
        $this->tceforms->doSaveFieldName = 'doSave';
        $this->tceforms->localizationMode = '';
        $this->tceforms->palettesCollapsed = 0;
    }

    /**
     * Loads the configuration of the virtual table 'tx_imageautoresize'.
     *
     * @return void
     */
    protected function loadVirtualTca()
    {
        $GLOBALS['TCA'][static::virtualTable] = include(ExtensionManagementUtility::extPath($this->extKey) . 'Configuration/TCA/Module/Options.php');
        ExtensionManagementUtility::addLLrefForTCAdescr(static::virtualTable, 'EXT:' . $this->extKey . '/Resource/Private/Language/locallang_csh_' . static::virtualTable . '.xlf');
    }

    /**
     * Persists FlexForm items by removing 'ID-' in front of new
     * items.
     *
     * @param array &$valueArray : by reference
     * @return void
     */
    protected function persistFlexForm(array &$valueArray)
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
     *
     * @return void
     */
    protected function addStatisticsAndSocialLink()
    {
        $fileName = PATH_site . 'typo3conf/.tx_imageautoresize';

        if (!is_file($fileName)) {
            return;
        }

        $data = json_decode(file_get_contents($fileName), true);
        if (!is_array($data) || !(isset($data['images']) && isset($data['bytes']))) {
            return;
        }

        $resourcesPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($this->extKey) . 'Resources/Public/';
        $pageRenderer = $this->moduleTemplate->getPageRenderer();
        $pageRenderer->addCssFile($resourcesPath . 'Css/twitter.css');
        $pageRenderer->addJsFile($resourcesPath . 'JavaScript/popup.js');

        $totalSpaceClaimed = GeneralUtility::formatSize((int)$data['bytes']);
        $messagePattern = $this->languageService->getLL('storage.claimed');
        $message = sprintf($messagePattern, $totalSpaceClaimed, (int)$data['images']);

        $flashMessage = htmlspecialchars($message);

        $twitterMessagePattern = $this->languageService->getLL('social.twitter');
        $message = sprintf($twitterMessagePattern, $totalSpaceClaimed);
        $url = 'https://typo3.org/extensions/repository/view/image_autoresize';

        $twitterLink = 'https://twitter.com/intent/tweet?text=' . urlencode($message) . '&url=' . urlencode($url);
        $twitterLink = GeneralUtility::quoteJSvalue($twitterLink);
        $flashMessage .= '
            <div class="custom-tweet-button">
                <a href="#" onclick="popitup(' . $twitterLink . ',\'twitter\')" title="' . $this->languageService->getLL('social.share', true) . '">
                    <i class="btn-icon"></i>
                    <span class="btn-text">Tweet</span>
                </a>
            </div>';

        $this->content .= '
            <div class="alert alert-info">
                <div class="media">
                    <div class="media-left">
                        <span class="fa-stack fa-lg">
                            <i class="fa fa-circle fa-stack-2x"></i>
                            <i class="fa fa-info fa-stack-1x"></i>
                        </span>
                    </div>
                    <div class="media-body">
                        ' . $flashMessage . '
                    </div>
                </div>
            </div>
        ';
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
