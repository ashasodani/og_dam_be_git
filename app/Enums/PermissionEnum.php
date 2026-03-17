<?php
namespace App\Enums;

enum PermissionEnum {
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
                    'list'   => 'roles_lists',
                    'create' => 'roles_create',
                    'update' => 'roles_update',
                    'delete' => 'roles_delete',
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
                "workspaces"           => [
                    'list'   => 'workspace_list',
                    'create' => 'workspace_create',
                    'update' => 'workspace_update',
                    'delete' => 'workspace_delete',
                    'view'   => 'workspace_view',
                ],
                "sections"             => [
                    'list'   => 'sections_list',
                    'create' => 'sections_create',
                    'update' => 'sections_update',
                    'delete' => 'sections_delete',
                    'view'   => 'sections_view',
                ],
                "portals"              => [
                    'list'   => 'portals_list',
                    'create' => 'portals_create',
                    'update' => 'portals_update',
                    'delete' => 'portals_delete',
                    'view'   => 'portals_view',
                ],
                "collections"          => [
                    'list'   => 'collections_list',
                    'create' => 'collections_create',
                    'update' => 'collections_update',
                    'delete' => 'collections_delete',
                    'view'   => 'collections_view',
                ],
                "tiles"                => [
                    'list'   => 'tiles_list',
                    'create' => 'tiles_create',
                    'update' => 'tiles_update',
                    'delete' => 'tiles_delete',
                    'view'   => 'tiles_view',
                ],
                "additional_links"     => [
                    'list'   => 'additional_links_list',
                    'create' => 'additional_links_create',
                    'update' => 'additional_links_update',
                    'delete' => 'additional_links_delete',
                    'view'   => 'additional_links_view',
                ],
                "labels"               => [
                    'list'   => 'labels_list',
                    'create' => 'labels_create',
                    'update' => 'labels_update',
                    'delete' => 'labels_delete',
                    'view'   => 'labels_view',
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
                "user" => [
                    'update'     => 'user_update',
                    'delete'     => 'user_delete',
                    'list' => 'user_list',
                    'create' => 'user_create',
                ],
                "sub_folders"          => [
                    'list'   => 'sub_folders_list',
                    'create' => 'sub_folders_create',
                    'update' => 'sub_folders_update',
                    'delete' => 'sub_folders_delete',
                    'view'   => 'sub_folders_view',
                ],
                "tags"                 => [
                    'list'   => 'tags_list',
                    'create' => 'tags_create',
                    'update' => 'tags_update',
                    'delete' => 'tags_delete',
                    'view'   => 'tags_view',
                ],
                "share_links"                 => [
                    'list'   => 'share_links_list',
                    'create' => 'share_links_create',
                    'update' => 'share_links_update',
                    'delete' => 'share_links_delete',
                    'view'   => 'share_links_view',
                ],
                "asset"                 => [
                    'list'   => 'asset_list',
                    'create' => 'asset_create',
                    'update' => 'asset_update',
                    'delete' => 'asset_delete',
                    'view'   => 'asset_view',
                    'share'   => 'asset_share',
                ],
                "countries"            => [ 
                    'list'   => 'country_list',
                    'create' => 'country_create',
                    'update' => 'country_update',
                    'delete' => 'country_delete',
                    'change_status' => 'change_status',
                ]   
            ]
        };
    }
}
