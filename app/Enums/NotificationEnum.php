<?php
namespace App\Enums;

enum NotificationEnum {
    case Slugs;
    /**
     * The permission slug array.
     */

    /**
     * Create a new class instance.
     */
    public function getAll(): array
    {
        return match ($this) {
            self::Slugs => [
                "sharelinks"                => [
                   'sharelink_asset_view' => 'Share Link Asset Viewed',
                    'sharelink_asset_download' => 'Share Link Asset Download',
                    
                ],
                "collection"           => [
                    'collection_view' => 'Collection Viewed',
                    'collection_asset_add' => 'Asset Added In Collection',
                ],
                "users"             => [
                    'invitation_accept' => 'Invitation Accepted',
                ],
                 "assets"             => [
                    'asset_update' => 'Asset Updated',
                ],
            ]
        };
    }
}
