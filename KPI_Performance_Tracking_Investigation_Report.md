# Investigation Report: Missing Quarter Selection in KPI Performance Tracking

## Executive Summary

The "Add Performance Tracking" modal in the KPI Performance Tracking section lacks a visible quarter selector dropdown. While the quarter is technically being set via a hidden field populated by JavaScript when clicking the "+" button in a specific quarter column, this implementation is not user-friendly and can lead to confusion. Additionally, there are several data retrieval and filtering issues that prevent proper quarter-based performance tracking.

---

## Detailed Findings

### 1. **Missing Visible Quarter Selector in Modal**

**Location:** `resources/views/pages/sector/kpis.blade.php` (Lines 671-735)

**Current Implementation:**
- The "Add Performance Tracking" modal has a hidden input field: `<input type="hidden" id="quarterX" name="quarter">` (Line 679)
- The quarter value is set via JavaScript when clicking the "+" button in a specific quarter column (Line 943)
- There is **NO visible dropdown or selector** for users to choose the quarter

**Impact:**
- Users cannot see or verify which quarter they're adding performance for
- Users cannot change the quarter if they accidentally clicked the wrong "+" button
- The quarter selection is implicit and not explicit, leading to potential user errors

---

### 2. **No Year Filtering When Retrieving Performance Tracks**

**Location:** `resources/views/pages/sector/kpis.blade.php` (Line 303)

**Current Implementation:**
```php
$tracks = $kpi->performanceTracking()->get();
```

**Issues:**
- Retrieves ALL performance tracking records for the KPI regardless of year
- The page has a `$year` variable (Line 112), but it's not being used to filter tracks
- The `performanceTracking()` relationship in `Kpi` model (Line 25-28) doesn't filter by year

**Impact:**
- Performance tracks from different years may be displayed together
- Array index assumptions (e.g., `$tracks[0]` = Q1) can be incorrect if tracks are missing or from different years

---

### 3. **Array Index Assumption for Quarter Mapping**

**Location:** `resources/views/pages/sector/kpis.blade.php` (Lines 341-539)

**Current Implementation:**
- Q1 Column: Uses `$tracks[0]` (Line 342)
- Q2 Column: Uses `$tracks[1]` (Line 392)
- Q3 Column: Uses `$tracks[2]` (Line 442)
- Q4 Column: Uses `$tracks[3]` (Line 492)

**Issues:**
- Assumes tracks are always ordered by quarter and that array indices directly map to quarters
- If Q1 is missing, `$tracks[0]` might actually be Q2, Q3, or Q4
- The relationship orders by quarter (`orderBy('quarter','ASC')`), but doesn't filter by quarter when accessing

**Impact:**
- Wrong quarter data may be displayed in the wrong column
- Missing quarters cause incorrect index mapping

---

### 4. **No Year Field Visible in Modal**

**Location:** `resources/views/pages/sector/kpis.blade.php` (Line 680)

**Current Implementation:**
- Year is passed as a hidden field: `<input type="hidden" id="year" name="year" value="{{$year}}">`
- Users cannot see or verify which year they're adding performance for

**Impact:**
- Users may accidentally add performance for the wrong year if the page year context is unclear

---

### 5. **Missing Quarter Validation in Controller**

**Location:** `app/Http/Controllers/KpiController.php` (Line 55-90)

**Current Implementation:**
```php
$tracking->fill($request->all());
```

**Issues:**
- No validation to ensure quarter is between 1-4
- No validation to ensure year matches the KPI's year
- No check to prevent duplicate entries for the same KPI/quarter/year combination

**Impact:**
- Invalid data may be saved (e.g., quarter = 5 or 0)
- Duplicate entries for the same quarter/year may be created

---

## Root Causes

1. **Design Assumption**: The UI assumes users will always click the "+" button in the correct quarter column, so the quarter doesn't need to be visible in the modal.

2. **Missing Filtering**: Performance tracks are retrieved without year/quarter filtering, leading to incorrect display.

3. **Index-Based Mapping**: Using array indices instead of filtering by quarter value causes incorrect quarter-to-column mapping.

---

## Recommendations

### Priority 1: Add Visible Quarter Selector

**Action:** Add a dropdown/select field in the "Add Performance Tracking" modal
- Options: Q1, Q2, Q3, Q4
- Pre-populate based on which column's "+" button was clicked, but allow user to change it
- Make it a required field

**Files to Modify:**
- `resources/views/pages/sector/kpis.blade.php` (Add quarter selector around Line 690)

---

### Priority 2: Filter Performance Tracks by Year

**Action:** Modify the retrieval to filter by the current year:
```php
$tracks = $kpi->performanceTracking()->where('year', $year)->get();
```

**Files to Modify:**
- `resources/views/pages/sector/kpis.blade.php` (Line 303)
- Consider updating the `Kpi` model's `performanceTracking()` relationship to accept a year parameter, or create a scoped method

---

### Priority 3: Filter by Quarter When Displaying

**Action:** Instead of using array indices, filter tracks by quarter value:
```php
$q1Track = $tracks->where('quarter', 1)->first();
$q2Track = $tracks->where('quarter', 2)->first();
// etc.
```

**Files to Modify:**
- `resources/views/pages/sector/kpis.blade.php` (Lines 341-539)

---

### Priority 4: Add Year Field Visibility

**Action:** Display the year in the modal header or as a read-only field
- Helps users confirm which year they're working with

**Files to Modify:**
- `resources/views/pages/sector/kpis.blade.php` (Modal section)

---

### Priority 5: Add Validation in Controller

**Action:** Add proper validation:
- Validate quarter: `'quarter' => 'required|integer|in:1,2,3,4'`
- Validate year: `'year' => 'required|integer|min:2000|max:2100'`
- Check for duplicates: Ensure no existing record for the same `kpi_id`, `quarter`, and `year` combination

**Files to Modify:**
- `app/Http/Controllers/KpiController.php` (Line 55-90)

---

### Priority 6: Improve Data Retrieval Logic

**Action:** Create a helper method in the `Kpi` model:
```php
public function getQuarterTrack($quarter, $year) {
    return $this->performanceTracking()
        ->where('quarter', $quarter)
        ->where('year', $year)
        ->first();
}
```

**Files to Modify:**
- `app/Models/Kpi.php`

---

## Files Requiring Changes

1. **`resources/views/pages/sector/kpis.blade.php`**
   - Add quarter selector dropdown in modal (Line ~690)
   - Filter tracks by year when retrieving (Line 303)
   - Use quarter filtering instead of array indices (Lines 341-539)
   - Display year in modal

2. **`app/Models/Kpi.php`**
   - Add method to get tracks filtered by year and quarter

3. **`app/Http/Controllers/KpiController.php`**
   - Add validation for quarter and year
   - Add duplicate check before saving

---

## Expected Outcomes After Fix

1. ✅ Users can clearly see and select the quarter when adding performance
2. ✅ Only tracks for the selected year are displayed
3. ✅ Correct quarter data appears in the correct columns
4. ✅ Year context is clear in the modal
5. ✅ Invalid data is prevented through validation
6. ✅ Duplicate entries are prevented

---

## Additional Notes

- The database schema properly supports quarter and year (`performance_trackings` table has both columns)
- The backend logic can handle quarter-based tracking
- The main issues are in the UI/UX and data retrieval/filtering logic

---

**Report Generated:** {{ date('Y-m-d H:i:s') }}  
**Investigated By:** AI Assistant  
**Status:** Investigation Complete - Awaiting Implementation
