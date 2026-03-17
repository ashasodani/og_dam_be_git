<?php

namespace App\Services;

use App\Repositories\DashboardRepository;
use Illuminate\Support\Facades\Auth;
use App\Models\Portals;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspaces;
use Illuminate\Support\Facades\Storage;

/**
 * Class DashboardService
 *
 * Service class for managing dashboard-related user data.
 *
 * @package App\Services
 */
class DashboardService
{
    /**
     * @var DashboardRepository
     */
    protected $dashboardRepository;

    /**
     * DashboardService constructor.
     *
     * @param DashboardRepository $dashboardRepository
     */
    public function __construct(DashboardRepository $dashboardRepository)
    {
        $this->dashboardRepository = $dashboardRepository;
    }

    /**
     * Get user workspaces and portals data.
     *
     * @param int $userId
     * @return array<string, mixed>
     */
    public function getUserWorkspacesAndPortals($request): array
    {
        $user = Auth::user();
        if ($user->getRoleNames()[0] == 'Super Admin') {
            return $this->getWorkspacesAndPortals($request, $user);
        } else {
            return $this->dashboardRepository->getUserWorkspacesAndPortals($request, $user);
        }
    }
    public function getWorkspacesAndPortals($request, $user): array
    {

        $workspaces = Workspaces::where('name', 'ILIKE', '%' . $request->input('search') . '%')->orderBy('id', 'desc')
            ->get()->map(function ($workspace) {
                return [
                    'id'   => $workspace->id,
                    'name' => $workspace->name,
                    'slug' => $workspace->slug,
                    'privacy' => $workspace->privacy,
                    'description' => $workspace->description,
                    'collections_count' => $workspace->collections()->count(),
                    'url' => (isset($workspace->url))
                        ? Storage::disk('s3')->temporaryUrl(
                            $workspace->url,
                            now()->addMinutes(30),
                            ['ResponseContentDisposition' => 'inline']
                        )
                        : 'https://picsum.photos/200/300?random=3',

                    'assets_count' => $workspace->assets()->where('is_completed', true)->count(),
                ];
            });

        $portals = Portals::where('name', 'ILIKE', '%' . $request->input('search') . '%')->orderBy('id', 'desc')
            ->get()->map(function ($portal) {


                return [
                    'id'   => $portal->id,
                    'name' => $portal->name,
                    'slug' => $portal->slug,
                    'collections_count' => 0,
                    'privacy' => $portal->privacy,
                    'description' => $portal->description,
                    'url' => (isset($portal->url))
                        ? Storage::disk('s3')->temporaryUrl(
                            $portal->url,
                            now()->addMinutes(30),
                            ['ResponseContentDisposition' => 'inline']
                        )
                        : 'https://picsum.photos/200/300?random=3',
                    'header_url' => (isset($portal->header_url))
                        ? Storage::disk('s3')->temporaryUrl(
                            $portal->header_url,
                            now()->addMinutes(30),
                            ['ResponseContentDisposition' => 'inline']
                        )
                        : 'https://picsum.photos/200/300?random=3',

                ];
            });
        return [
            'user'       => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'workspaces' => $workspaces,
            'portals'    => $portals,
        ];
    }
}
