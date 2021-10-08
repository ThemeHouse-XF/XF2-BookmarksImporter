<?php

namespace ThemeHouse\BookmarksImporter\Import\Importer;

class THBookmarks extends AbstractBookmarkImporter
{
    public static function getListInfo()
    {
        return [
            'target' => 'XenForo 2.1 Bookmarks',
            'source' => '[TH] Bookmarks',
        ];
    }

    protected function getSupportedContentTypes()
    {
        $supportedContentTypes = [
            'node',
            'post',
        ];

        $addOns = $this->app->registry()['addOns'];

        if (isset($addOns['XFMG'])) {
            $supportedContentTypes[] = 'xfmg_media';
        }

        if (isset($addOns['XFRM'])) {
            $supportedContentTypes[] = 'resource';
        }

        return $supportedContentTypes;
    }

    public function getStepEndBookmarks()
    {
        return $this->db()->fetchOne("
            SELECT MAX(bookmark_id)
            FROM xf_th_bookmark") ?: 0;
    }

    protected function getBookmarks($startAfter, $end, $limit)
    {
        return $this->db()->fetchAllKeyed("
            SELECT bookmark.*, user.username
            FROM xf_th_bookmark AS bookmark
            LEFT JOIN xf_user AS user ON user.user_id = bookmark.user_id
            WHERE bookmark.bookmark_id > ? AND bookmark.bookmark_id <= ?
            ORDER BY bookmark.bookmark_id
            LIMIT {$limit}", 'bookmark_id', [$startAfter, $end]);
    }

    protected function mapFields(array $existingBookmark)
    {
        if ($existingBookmark['content_type'] === 'thread') {
            /** @var \XF\Entity\Thread $thread */
            $thread = $this->app->em()->find('XF:Thread', $existingBookmark['content_id']);
            if (!$thread) return false;

            $existingBookmark['content_type'] = 'post';
            $existingBookmark['content_id'] = $thread->first_post_id;
        }

        return [
            'user_id' => $existingBookmark['user_id'],
            'content_type' => $existingBookmark['content_type'],
            'content_id' => $existingBookmark['content_id'],
            'message' => $existingBookmark['note'],
            'bookmark_date' => $existingBookmark['bookmark_date'] ?: \XF::$time,
        ];
    }

}
