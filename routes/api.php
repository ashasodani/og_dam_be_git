<?php

use App\Http\Controllers\API\AssetActionController;
use App\Http\Controllers\API\AssetController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Auth\PasswordController;
use App\Http\Controllers\API\CollectionController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\DropboxController;
use App\Http\Controllers\API\ExternalLinkController;
use App\Http\Controllers\API\LabelController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\PortalController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\SectionController;
use App\Http\Controllers\API\ShareLinkController;
use App\Http\Controllers\API\SubFolderController;
use App\Http\Controllers\API\TagController;
use App\Http\Controllers\API\CountryController;
use App\Http\Controllers\API\CompanyController;
use App\Http\Controllers\API\TilesController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\UserInvitationController;
use App\Http\Controllers\API\WorkspaceController;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('signin', 'login');
    Route::post('onboard', 'onBoard');
    Route::post('onboard/verify', 'onBoardVerify');
});

Route::controller(PasswordController::class)->group(function () {
    Route::post('forgot', 'forgot');
    Route::post('password/reset', 'reset')->name('password.reset');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::resource('role', RoleController::class);
    Route::get('getpermissiongroup', [RoleController::class, 'getPermissionGroup']);
    Route::get('getrolepermission/{roleId}', [RoleController::class, 'getRolePermission']);
    Route::post('roleAssign', [RoleController::class, 'assignRole']);
    Route::post('permissionAssign', [PermissionController::class, 'assignPermission']);
    Route::resource('workspaces', WorkspaceController::class);
    Route::get('workspaces/getBySlug/{slug}', [WorkspaceController::class, 'getSlugWorkspace']);
    Route::post('workspaces/{id}', [WorkspaceController::class, 'updateWorkspace']);
    Route::resource('sections', SectionController::class);
    Route::get('getAllAsset', [SectionController::class, 'getAllAssets']);
     Route::get('getAllAsset/{id}', [SectionController::class, 'getAllAssetsId']);
    Route::put('sectionsPosition/{id}', [SectionController::class, 'updatePosition']);
    Route::post('update_sections_position', [SectionController::class, 'updateSectionsPosition']);
    Route::get('getsection', [SectionController::class, 'getAllSection']);
    Route::resource('portals', PortalController::class);

    Route::post('portals/{id}', [PortalController::class, 'updatePortal']);
    
    Route::post('related_portals', [PortalController::class, 'createRelatedPortal']);
    Route::post('edit_related_portals', [PortalController::class, 'createRelatedPortal']);

  
    Route::post('tiles/{id}', [TilesController::class, 'updateTiles']);
    Route::put('tilesPosition/{id}', [TilesController::class, 'updatePosition']);
    Route::post('update_tile_position', [TilesController::class, 'updateTilePosition']);

    
    Route::post('additionallinks/{id}', [ExternalLinkController::class, 'updateLinks']);
    Route::put('linkPosition/{id}', [ExternalLinkController::class, 'updatePosition']);
    Route::post('update_additional_link_position', [ExternalLinkController::class, 'updateAdditionalLinkPosition']);

    Route::resource('collections', CollectionController::class);
    Route::get('collection/asset/{slug}', [CollectionController::class, 'getCollectionAssets']);
    Route::resource('labels', LabelController::class);
    Route::get('parentlabel', [LabelController::class, 'getParentLabels']);
    Route::resource('invite_users', UserInvitationController::class);
    // Route::post('resend_invite_users', [UserInvitationController::class, 'resendInvitation']);
    Route::post('resend_invite_users', [UserInvitationController::class, 'resendInvitation']);
    Route::resource('users', UserController::class);
    Route::post('assign_resource', [UserController::class, 'assignResource']);
    Route::post('update_assign_resource/{id}', [UserController::class, 'updateAssignResource']);
    Route::get('get_assigned_resource', [UserController::class, 'getAssignedResource']);
    Route::resource('subfolders', SubFolderController::class);
    Route::resource('assests', AssetController::class);
    Route::resource('tags', TagController::class);
    Route::resource('countries', CountryController::class);
    Route::resource('assests', AssetController::class);
    Route::post('assets/types', [AssetController::class, 'assetTypeCreate']);

    Route::get('assets/{assetKey}', [AssetController::class, 'showAssetKey']);
    
    Route::post('assests/delete', [AssetActionController::class, 'deleteAsset']);
    Route::post('assests/move', [AssetActionController::class, 'assetMove']);
    Route::post('assests/thumbnails', [AssetController::class, 'assetThumbnails']);
    Route::post('assests/copy', [AssetActionController::class, 'assetCopy']);
    Route::post('assests/assign-folder', [AssetActionController::class, 'assetFolderAssign']);
    Route::post('assign-sharelink', [AssetActionController::class, 'assetSharelinkAssign']);
    Route::post('assests/assign-label', [AssetActionController::class, 'assignLabel']);
    Route::get('/user/workspaces-portals', [DashboardController::class, 'getUserWorkspacesAndPortals']);
    Route::post('checkunique', [WorkspaceController::class, 'checkUnique']);
    Route::get('dropbox/auth', [DropboxController::class, 'redirectToDropbox']);
    Route::get('/dropbox/callback', [DropboxController::class, 'handleDropboxCallback']);
    Route::post('/dropbox/token', [DropboxController::class, 'saveDropboxToken']);
    Route::get('/dropbox/list-files', [DropboxController::class, 'listFiles']);
    Route::resource('sharelinks', ShareLinkController::class);
    Route::get('/share-links/counts', [ShareLinkController::class, 'counts']);
    Route::get('/share-links/created-users', [ShareLinkController::class, 'createdUser']);
    Route::post('/share-links/remove/{id}', [ShareLinkController::class, 'removeAssets']);
    Route::post('assets/upload-chunk', [AssetController::class, 'uploadChunk']);
    Route::post('assets/upload-chunk/{id}', [AssetController::class, 'uploadChunk']);
    Route::post('assets/{id}', [AssetController::class, 'assetUpdate']);
    Route::post('asset/tags/{id}', [AssetController::class, 'assetViewTag']);
    Route::post('asset/label/{id}', [AssetController::class, 'assetViewLabel']);
    Route::get('asset/log/{id}', [AssetActionController::class, 'assetUpdateLog']);
    Route::post('assign-collection', [AssetActionController::class, 'assignToCollections']);
    Route::post('remove-collection', [AssetActionController::class, 'removeFromCollections']);
    Route::post('/collection/remove/{id}', [AssetActionController::class, 'removeAssets']);
    Route::post('assign-label', [AssetActionController::class, 'assignToLabels']);
    Route::post('assign-tag', [AssetActionController::class, 'assignToTags']);
    Route::post('common_tag', [AssetActionController::class, 'commonTags']);
    Route::post('remove-label', [AssetActionController::class, 'removeFromLabels']);
    Route::post('/workspace-notifications', [NotificationController::class, 'workspaceNotificationStore']);
    Route::get('/getworkspace', [NotificationController::class, 'getWorkspaceNotification']);
    Route::get('/getnotification', [NotificationController::class, 'getNotification']);
    Route::get('/notification/filter', [NotificationController::class, 'getFilter']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::get('/notification/count', [NotificationController::class, 'getNotificationCounts']);
    Route::get('gettag', [TagController::class, 'getTag']);
    Route::get('getcountry', [CountryController::class, 'getCountries']);
    Route::get('getcity/{id}', [CountryController::class, 'getCities']);
    Route::get('getdirectory', [CompanyController::class, 'getDirectory']);
    Route::get('companies/{id}/persons', [CompanyController::class, 'getPersons']);
     Route::get('getport', [CountryController::class, 'getPorts']);
    Route::get('getlabel', [LabelController::class, 'getLabel']);
    Route::get('verifytoken', [AuthController::class, 'checkToken']);
    Route::post('change-password', [PasswordController::class, 'changePassword']);
    Route::get('/search/suggestions', [SectionController::class, 'suggestions']);
     Route::get('/collection/search/suggestions', [CollectionController::class, 'suggestions']);
});
Route::get('getextension', [AssetController::class, 'getExtension']);
Route::get('portal/{id}', [PortalController::class, 'showPortal']);
Route::get('asset/{assetkey}/download', [AssetController::class, 'assetDownload']);
Route::post('asset/download/zip/encode', [AssetController::class, 'assetZipEncode']);
Route::get('asset/download/zip/{encoded}', [AssetController::class, 'assetZipDownload']);
Route::get('asset/{assetkey}', [AssetController::class, 'assetViewer']);
Route::get('asset/videostream/{assetkey}', [AssetController::class, 'assetViewerVideo']);
Route::get('asset/video/{assetkey}', [AssetController::class, 'assetVideoViewer']);
Route::get('asset/video/{assetkey}/download', [AssetController::class, 'assetVideoDownload']);
Route::get('asset/thumb/{assetkey}', [AssetController::class, 'assetThumbViewer']);
Route::get('asset/thumb/{assetkey}/download', [AssetController::class, 'assetThumbDownload']);
Route::post('sharelink/info', [ShareLinkController::class, 'sharelinkInfo']);
Route::post('sharelink/guest/{id}', [ShareLinkController::class, 'sharelinkGuestInfo']);
Route::get('subfolder/{id}', [SubFolderController::class, 'show']);
Route::get('sharelink-log/{id}', [ShareLinkController::class, 'viewShareLinkLog']);
Route::get('portals/getBySlug/{slug}', [PortalController::class, 'getSlugPortals']);
Route::get('get_related_portals', [PortalController::class, 'getRelatedPortal']);
Route::resource('tiles', TilesController::class);
Route::get('memberdetail/status/{id}',  [UserController::class, 'getMemberDetail']);
Route::put('memberdetail/status/{id}',  [UserController::class, 'updateMemberStatus']);

Route::resource('companies', CompanyController::class);

Route::resource('additionallinks', ExternalLinkController::class);
Route::get('asset/audio/{assetkey}', [AssetController::class, 'assetAudioViewer']);
//Route::get('subfolder/{id}', SubFolderController::class);
