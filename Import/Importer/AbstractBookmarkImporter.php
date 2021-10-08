<?php

namespace ThemeHouse\BookmarksImporter\Import\Importer;

use XF\Import\Importer\AbstractImporter;
use XF\Import\StepState;

abstract class AbstractBookmarkImporter extends AbstractImporter
{
    public function canRetainIds()
    {
        return false;
    }

    public function getFinalizeJobs(array $stepsRun)
    {
        return [];
    }

    public function resetDataForRetainIds()
    {
        return false;
    }

    protected function getBaseConfigDefault()
    {
        return [];
    }

    public function renderBaseConfigOptions(array $vars)
    {
        $supportedContentTypes = $this->getSupportedContentTypes();
        $vars['supportedContentTypes'] = [];
        foreach ($supportedContentTypes as $contentType) {
            $vars['supportedContentTypes'][] = [
                'value' => $contentType,
                'label' => \XF::app()->getContentTypePhrase($contentType, true),
            ];
        }
        return $this->app->templater()->renderTemplate('admin:th_bookmarksimport_import_config', $vars);
    }

    public function validateBaseConfig(array &$baseConfig, array &$errors)
    {
        $supportedContentTypes = $this->getSupportedContentTypes();

        foreach ($baseConfig['content_types'] as $key=>$contentType) {
            if (!in_array($contentType, $supportedContentTypes)) {
                unset($baseConfig['content_types'][$key]);
            }
        }

        if (empty($baseConfig)) {
            $errors[] = \XF::phrase('th_bookmarksimport_must_select_content_types');
        }

        return true;
    }

    protected function getStepConfigDefault()
    {
        return [];
    }

    public function renderStepConfigOptions(array $vars)
    {
        return null;
    }

    public function validateStepConfig(array $steps, array &$stepConfig, array &$errors)
    {
        return true;
    }

    public function getSteps()
    {
        return [
            'bookmarks' => [
                'title' => \XF::phrase('bookmarks'),
            ],
        ];
    }

    public function stepBookmarks(StepState $state, array $stepConfig, $maxTime, $limit = 50)
    {
        $timer = new \XF\Timer($maxTime);
        $bookmarks = $this->getBookmarks($state->startAfter, $state->end, $limit);

        foreach ($bookmarks as $oldId => $existingBookmark) {
            $state->startAfter = $oldId;
            $data = $this->mapFields($existingBookmark);
            if (!$data) continue;

            /** @var \XF\Import\Data\BookmarkItem $import */
            $import = $this->newHandler('XF:BookmarkItem');
            $import->bulkSet($data);
            try {
                $import->save($oldId);
                $state->imported++;
            } catch (\Exception $e) {}

            if ($timer->limitExceeded()) {
                break;
            }
        }

        return $state->resumeIfNeeded();
    }

    protected function doInitializeSource() {}

    public abstract function getStepEndBookmarks();
    protected abstract function getBookmarks($startAfter, $end, $limit);
    protected abstract function mapFields(array $existingBookmark);
    protected abstract function getSupportedContentTypes();
}
