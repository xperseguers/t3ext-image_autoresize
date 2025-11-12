<?php

declare(strict_types=1);

use Causal\ImageAutoresize\Command\BatchResize;
use Causal\ImageAutoresize\Controller\FormFlexAjaxController;
use Causal\ImageAutoresize\EventListener\CoreResourceStorageEventListener;
use Causal\ImageAutoresize\EventListener\ExtensionManagerEventListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileReplacedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileAddedEvent;
use TYPO3\CMS\Core\Resource\Event\SanitizeFileNameEvent;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(FormFlexAjaxController::class)
        ->public();

    $services->set(BatchResize::class)
        ->tag('console.command', [
        'command' => 'imageautoresize:batchresize',
        'schedulable' => true,
        'description' => 'Searches for files that were not resized automatically during upload (e.g., because they were uploaded through a direct mechanism such as FTP) and resizes them according to your rules. Group-related rules are skipped.',
    ]);

    $services->set(ExtensionManagerEventListener::class)
        ->tag('event.listener', [
        'identifier' => 'causal/image_autoresize',
        'method' => 'processActions',
        'event' => 'TYPO3\CMS\Extensionmanager\Event\AvailableActionsForExtensionEvent',
    ]);

    $services->set(CoreResourceStorageEventListener::class)
        ->tag('event.listener', [
        'identifier' => 'causal/image_autoresize',
        'method' => 'sanitizeFileName',
        'event' => SanitizeFileNameEvent::class,
    ])
        ->tag('event.listener', [
        'identifier' => 'causal/image_autoresize',
        'method' => 'afterFileReplaced',
        'event' => AfterFileReplacedEvent::class,
    ])
        ->tag('event.listener', [
        'identifier' => 'causal/image_autoresize',
        'method' => 'beforeFileAdded',
        'event' => BeforeFileAddedEvent::class,
    ])
        ->tag('event.listener', [
        'identifier' => 'causal/image_autoresize',
        'method' => 'populateMetadata',
        'event' => AfterFileAddedEvent::class,
    ]);
};
