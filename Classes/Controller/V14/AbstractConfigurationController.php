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

namespace Causal\ImageAutoresize\Controller\V14;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormResultCollection;
use TYPO3\CMS\Backend\Form\FormResultFactory;
use TYPO3\CMS\Backend\Form\FormResultHandler;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractConfigurationController
{
    protected readonly int $typo3Version;

    protected readonly LanguageService $languageService;

    protected array $config;

    /**
     * Default constructor
     */
    public function __construct(
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleProvider $moduleProvider,
        private readonly FormDataCompiler $formDataCompiler,
        private readonly FormResultFactory $formResultFactory,
        private readonly FormResultHandler $formResultHandler
    )
    {
        $this->typo3Version = (new Typo3Version())->getMajorVersion();
        $this->languageService = $GLOBALS['LANG'];
        $this->config = static::readConfiguration();
        $this->config['conversion_mapping'] = implode(LF, explode(',', $this->config['conversion_mapping']));
    }

    abstract protected static function readConfiguration(): array;

    /**
     * Generates the module content.
     *
     * @param ServerRequestInterface $request
     * @param array $row
     */
    protected function moduleContent(ServerRequestInterface $request, array $row): void
    {
        $wizard = $this->buildForm($request, $row);
        $this->content .= $wizard;
    }

    /**
     * Builds the expert configuration form.
     *
     * @param ServerRequestInterface $request
     * @param array $row
     * @return string
     */
    protected function buildForm(ServerRequestInterface $request, array $row): string
    {
        $record = [
            'uid' => static::virtualRecordId,
            'pid' => 0,
        ];
        $record = array_merge($record, $row);

        // Trick to use a virtual record
        $dataProviders =& $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'];

        $dataProviders[\Causal\ImageAutoresize\Backend\Form\FormDataProvider\VirtualDatabaseEditRow::class] = [
            'before' => [
                \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class,
            ]
        ];

        // Initialize record in our virtual provider
        \Causal\ImageAutoresize\Backend\Form\FormDataProvider\VirtualDatabaseEditRow::initialize($record);

        /** @var \TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord $formDataGroup */
        $formDataGroup = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord::class);
        $formDataCompilerInput = [
            'request' => $request,
            'tableName' => static::virtualTable,
            'vanillaUid' => $record['uid'],
            'command' => 'edit',
            'returnUrl' => '',
        ];

        // Load the configuration of virtual table 'tx_imageautoresize'
        $this->loadVirtualTca();

        $formData = $this->formDataCompiler->compile($formDataCompilerInput, $formDataGroup);
        $formData['renderType'] = 'outerWrapContainer';
        /** @var \TYPO3\CMS\Backend\Form\NodeFactory $nodeFactory */
        $nodeFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\NodeFactory::class);
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

        $formResult = $this->formResultFactory->create($formResult);
        $formResults = new FormResultCollection();
        $formResults->add($formResult);
        $this->formResultHandler->addAssets($formResults);
        $foo = $formResults->getHtml();
        $html .= $foo;

        // Combine it all
        $formContent = '
			<!-- EDITING FORM -->
			' . $html . '

			<input type="hidden" name="returnUrl" value="' . htmlspecialchars($this->retUrl) . '" />
			<input type="hidden" name="closeDoc" value="0" />
			<input type="hidden" name="doSave" value="0" />
			<input type="hidden" name="_serialNumber" value="' . md5(microtime()) . '" />
			<input type="hidden" name="_scrollPosition" value="" />';

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $moduleUrl = (string)$uriBuilder->buildUriFromRoute('TxImageAutoresize::record_flex_container_add');
        $overriddenAjaxUrl = GeneralUtility::quoteJSvalue($moduleUrl);

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $class = new \ReflectionClass($pageRenderer);
        $property = $class->getProperty('nonce');
        $nonce = $property->getValue($pageRenderer)->consume();

        $formContent .= <<<HTML
<script type="text/javascript" nonce="$nonce">
    var scripts = document.querySelectorAll('script');
    for (var i = 0; i < scripts.length; i++) {
        const script = scripts[i];
        if (script.src.indexOf('/java-script-item-handler.js') !== -1) {
            script.addEventListener('load', function() {
                setTimeout(function () {
                    TYPO3.settings.ajaxUrls.record_flex_container_add = $overriddenAjaxUrl;
                }, 400);    // to be safe on slower machines
            });
        }
    }
</script>
HTML;

        return $formContent;
    }
}
