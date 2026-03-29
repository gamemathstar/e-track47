<?php

if (! function_exists('sector_view_url')) {
    /**
     * URL for the sector detail view (route name sectors.view).
     */
    function sector_view_url(int|string $sectorId, int|string|null $id2 = null): string
    {
        $params = ['id' => $sectorId];
        if ($id2 !== null) {
            $params['id2'] = $id2;
        }

        return route('sectors.view', $params);
    }
}
