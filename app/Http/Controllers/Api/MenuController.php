<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MenuService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MenuController extends Controller
{
    protected $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    /**
     * Get main menu for the current user
     */
    public function mainMenu(Request $request)
    {
        try {
            $user = $request->user();
            $menu = $this->menuService->getUserMenu($user);

            return response()->json([
                'success' => true,
                'data' => $menu,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get sidebar menu for the current user
     */
    public function sidebarMenu(Request $request)
    {
        try {
            $user = $request->user();

            // Handle unauthenticated requests
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                    'data' => []
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Use the service to get role-filtered menu
            $menu = $this->menuService->getSidebarMenu($user);

            return response()->json([
                'success' => true,
                'data' => array_values($menu),
            ]);
        } catch (\Exception $e) {
            \Log::error('Menu API Error: ' . $e->getMessage(), [
                'request_path' => $request->path(),
                'user_id' => $request->user() ? $request->user()->id : null,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error loading menu',
                'data' => []
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get menu by category
     */
    public function getByCategory(Request $request, string $category)
    {
        try {
            $menu = $this->menuService->getMenuByCategory($category);

            return response()->json([
                'success' => true,
                'data' => $menu,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all active menu items
     */
    public function index()
    {
        try {
            $menu = $this->menuService->getAllMenuItems();

            return response()->json([
                'success' => true,
                'data' => $menu,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get breadcrumbs for a route
     */
    public function breadcrumbs(Request $request)
    {
        try {
            $route = $request->query('route');

            if (!$route) {
                return response()->json([
                    'success' => false,
                    'message' => 'Route parameter is required',
                ], Response::HTTP_BAD_REQUEST);
            }

            $breadcrumbs = $this->menuService->getBreadcrumbs($route);

            return response()->json([
                'success' => true,
                'data' => $breadcrumbs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Toggle menu item visibility
     */
    public function toggle(Request $request, int $id)
    {
        try {
            // Check authorization
            $this->authorize('manage-menu-items');

            $result = $this->menuService->toggleMenuItem($id);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu item not found',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'success' => true,
                'message' => 'Menu item visibility toggled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
