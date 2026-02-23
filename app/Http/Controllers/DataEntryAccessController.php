<?php

namespace App\Http\Controllers;

use App\Models\DataEntryAccess;
use App\Models\Sector;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DataEntryAccessController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth");
    }

    /**
     * Display the data entry window management page
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Only Coordinators and Deputy Coordinators can access this page
        if (!$user->isCoordinator() && !$user->isDeputyCoordinator()) {
            return redirect()->route('dashboard')->with('failure', 'You do not have permission to access this page.');
        }

        $year = $request->input('year', Carbon::now()->year);
        $quarter = $request->input('quarter', DataEntryAccess::getCurrentQuarter());

        // Get all sectors
        $sectors = Sector::select('id', 'sector_name')
            ->orderBy('sector_name')
            ->get();

        // Initialize access records for all sectors if they don't exist
        $this->initializeQuarter($year, $quarter);

        // Get access records for the selected quarter/year
        $accessRecords = DataEntryAccess::with(['sector', 'grantedBy'])
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->get()
            ->keyBy('sector_id');

        // Ensure all sectors have records (fallback)
        foreach ($sectors as $sector) {
            if (!isset($accessRecords[$sector->id])) {
                $deadline = DataEntryAccess::calculateDeadline($year, $quarter);
                $isOpen = Carbon::now()->lte($deadline);

                $accessRecords[$sector->id] = (object)[
                    'id' => null,
                    'sector_id' => $sector->id,
                    'sector' => $sector,
                    'year' => $year,
                    'quarter' => $quarter,
                    'deadline_date' => $deadline,
                    'status' => $isOpen ? 'open' : 'closed',
                    'override_deadline' => null,
                    'override_reason' => null,
                    'granted_by' => null,
                    'granted_at' => null,
                    'grantedBy' => null,
                ];
            }
        }

        // Calculate statistics for the selected year/quarter
        $totalSectors = $sectors->count();
        
        // Count open sectors (status is open or override, and deadline hasn't passed)
        $openCount = $accessRecords->filter(function ($record) use ($year, $quarter) {
            if (is_object($record) && isset($record->status)) {
                // Ensure this record matches the selected year and quarter
                $recordYear = isset($record->year) ? $record->year : null;
                $recordQuarter = isset($record->quarter) ? $record->quarter : null;
                
                if ($recordYear != $year || $recordQuarter != $quarter) {
                    return false;
                }
                
                // Check if status allows access (open or override)
                if ($record->status === 'closed') {
                    return false;
                }
                
                // Get the deadline (override_deadline takes precedence)
                $deadline = $record->override_deadline ?? $record->deadline_date;
                if (!$deadline) {
                    return false;
                }
                
                // Parse deadline if it's a string
                $deadlineDate = $deadline instanceof Carbon ? $deadline : Carbon::parse($deadline);
                
                // Check if deadline hasn't passed
                return Carbon::now()->lte($deadlineDate);
            }
            return false;
        })->count();

        // Get access log (recent overrides)
        $accessLog = DataEntryAccess::with(['sector', 'grantedBy'])
            ->whereNotNull('granted_by')
            ->orderBy('granted_at', 'desc')
            ->limit(20)
            ->get();

        return view('pages.data-entry.index', compact(
            'sectors',
            'accessRecords',
            'year',
            'quarter',
            'totalSectors',
            'openCount',
            'accessLog'
        ));
    }

    /**
     * Grant override access to a sector
     */
    public function grantOverride(Request $request)
    {
        $user = Auth::user();

        // Only Coordinators and Deputy Coordinators can grant overrides
        if (!$user->isCoordinator() && !$user->isDeputyCoordinator()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to grant data entry access.'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'sector_id' => 'required|exists:sectors,id',
                'year' => 'required|integer|min:2020|max:2100',
                'quarter' => 'required|integer|in:1,2,3,4',
                'override_deadline' => 'required|date|after:today',
                'override_reason' => 'required|string|max:1000',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed. Please check your inputs.',
                'errors' => $e->errors()
            ], 422);
        }

        // Get or create access record
        $access = DataEntryAccess::firstOrNew([
            'sector_id' => $validated['sector_id'],
            'year' => $validated['year'],
            'quarter' => $validated['quarter'],
        ]);

        // Set deadline if not already set
        if (!$access->deadline_date) {
            $access->deadline_date = DataEntryAccess::calculateDeadline($validated['year'], $validated['quarter']);
        }

        $access->status = 'override';
        $access->override_deadline = $validated['override_deadline'];
        $access->override_reason = $validated['override_reason'];
        $access->granted_by = $user->id;
        $access->granted_at = Carbon::now();
        $access->save();

        return response()->json([
            'success' => true,
            'message' => 'Data entry access granted successfully.',
            'access' => $access->load(['sector', 'grantedBy'])
        ]);
    }

    /**
     * Lock all sectors for a quarter
     */
    public function lockAll(Request $request)
    {
        $user = Auth::user();

        // Only Coordinators and Deputy Coordinators can lock
        if (!$user->isCoordinator() && !$user->isDeputyCoordinator()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to lock data entry access.'
            ], 403);
        }

        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'quarter' => 'required|integer|in:1,2,3,4',
        ]);

        $sectors = Sector::pluck('id');

        foreach ($sectors as $sectorId) {
            $access = DataEntryAccess::firstOrNew([
                'sector_id' => $sectorId,
                'year' => $request->year,
                'quarter' => $request->quarter,
            ]);

            if (!$access->deadline_date) {
                $access->deadline_date = DataEntryAccess::calculateDeadline($request->year, $request->quarter);
            }

            $access->status = 'closed';
            $access->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'All sectors locked successfully.'
        ]);
    }

    /**
     * Unlock all sectors for a quarter (within normal deadline)
     */
    public function unlockAll(Request $request)
    {
        $user = Auth::user();

        // Only Coordinators and Deputy Coordinators can unlock
        if (!$user->isCoordinator() && !$user->isDeputyCoordinator()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to unlock data entry access.'
            ], 403);
        }

        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'quarter' => 'required|integer|in:1,2,3,4',
        ]);

        $deadline = DataEntryAccess::calculateDeadline($request->year, $request->quarter);

        // Only unlock if we're still within the normal deadline
        if (Carbon::now()->gt($deadline)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot unlock: Normal deadline has passed. Use manual override instead.'
            ], 400);
        }

        $sectors = Sector::pluck('id');

        foreach ($sectors as $sectorId) {
            $access = DataEntryAccess::firstOrNew([
                'sector_id' => $sectorId,
                'year' => $request->year,
                'quarter' => $request->quarter,
            ]);

            $access->deadline_date = $deadline;
            $access->status = 'open';
            $access->override_deadline = null;
            $access->override_reason = null;
            $access->granted_by = null;
            $access->granted_at = null;
            $access->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'All sectors unlocked successfully.'
        ]);
    }

    /**
     * Initialize access records for all sectors for a quarter
     * This is called automatically when viewing a quarter
     * 
     * @param int|null $year The year (optional, defaults to current year)
     * @param int|null $quarter The quarter (optional, defaults to current quarter)
     * @param Request|null $request Optional request object for API calls
     */
    public function initializeQuarter($year = null, $quarter = null, Request $request = null)
    {
        // If called via route (API), validate from request
        if ($request !== null) {
            $request->validate([
                'year' => 'required|integer|min:2020|max:2100',
                'quarter' => 'required|integer|in:1,2,3,4',
            ]);
            $year = $request->input('year');
            $quarter = $request->input('quarter');
        } else {
            // If called internally, use provided values or defaults
            $year = $year ?? Carbon::now()->year;
            $quarter = $quarter ?? DataEntryAccess::getCurrentQuarter();
            
            // Validate the values
            if (!is_numeric($year) || $year < 2020 || $year > 2100) {
                throw new \InvalidArgumentException('Invalid year. Must be between 2020 and 2100.');
            }
            if (!in_array($quarter, [1, 2, 3, 4])) {
                throw new \InvalidArgumentException('Invalid quarter. Must be 1, 2, 3, or 4.');
            }
        }

        $sectors = Sector::pluck('id');
        $deadline = DataEntryAccess::calculateDeadline($year, $quarter);
        $isOpen = Carbon::now()->lte($deadline);

        foreach ($sectors as $sectorId) {
            DataEntryAccess::firstOrCreate(
                [
                    'sector_id' => $sectorId,
                    'year' => $year,
                    'quarter' => $quarter,
                ],
                [
                    'deadline_date' => $deadline,
                    'status' => $isOpen ? 'open' : 'closed',
                ]
            );
        }

        // Only return JSON response if this is an API request
        if ($request !== null && ($request->expectsJson() || $request->ajax())) {
            return response()->json([
                'success' => true,
                'message' => 'Quarter initialized successfully.'
            ]);
        }
        
        // Otherwise, return null (called internally from index)
        return null;
    }
}
