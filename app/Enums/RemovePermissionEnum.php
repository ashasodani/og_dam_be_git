<?php

namespace App\Enums;

enum RemovePermissionEnum
{
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
                "roles"                => [
                    'view'   => 'roles_view',
                    'assign' => 'roles_assign',
                ],
                "permission"           => [
                    'list'   => 'permission_list',
                    'create' => 'permission_create',
                    'update' => 'permission_update',
                    'delete' => 'permission_delete',
                    'view'   => 'permission_view',
                    'assign' => 'permission_assign',
                ],
                "products"             => [
                    'list'   => 'product_list',
                    'create' => 'product_create',
                    'update' => 'product_update',
                    'delete' => 'product_delete',
                    'view'   => 'product_view',
                ],
                "tiles"                => [
                    'list'   => 'tiles_list',
                    'create' => 'tiles_create',
                    'update' => 'tiles_update',
                    'delete' => 'tiles_delete',
                    'view'   => 'tiles_view',
                ],
                "labels"               => [
                    'view'   => 'labels_view',
                ],
                "additional_links"     => [
                    'list'   => 'additional_links_list',
                    'create' => 'additional_links_create',
                    'update' => 'additional_links_update',
                    'delete' => 'additional_links_delete',
                    'view'   => 'additional_links_view',
                ],
                "invite_user"          => [
                    'list'        => 'invite_user_create',
                    'create'      => 'invite_user_list',
                    'delete'      => 'invite_user_delete',
                    'resend_link' => 'invite_user_resend_link',
                ],
                "assign_user_resource" => [
                    'assign_resource'     => 'assign_resource',
                    'assign_update'     => 'assign_resource_update',
                    'assign_resource_delete'     => 'assign_resource_delete',
                    'get_assign_resource' => 'get_assign_resource',
                ],

                "sub_folders"          => [
                    'list'   => 'sub_folders_list',
                    'create' => 'sub_folders_create',
                    'update' => 'sub_folders_update',
                    'delete' => 'sub_folders_delete',
                    'view'   => 'sub_folders_view',
                ],
                "workspaces"           => [
                    'view'   => 'workspace_view',
                ],
                "sections"             => [
                    'view'   => 'sections_view',
                ],
                "portals"              => [
                    'view'   => 'portals_view',
                ],
                "collections"          => [
                    'view'   => 'collections_view',
                ],
                "tags"                 => [
                    'view'   => 'tags_view',
                ],
                "share_links"                 => [
                    'view'   => 'share_links_view',
                ],
                "asset"                 => [
                    'view'   => 'asset_view',
                ],
            ]
        };
    }
}
