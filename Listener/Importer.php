<?php

namespace ThemeHouse\BookmarksImporter\Listener;

class Importer
{
    public static function importImporterClasses(\XF\SubContainer\Import $container, \XF\Container $parentContainer, array &$importers)
    {
        $importers = array_merge(
            $importers, \XF\Import\Manager::getImporterShortNamesForType('ThemeHouse/BookmarksImporter')
        );
    }
}