<?php

if (!function_exists('sector_view_url')) {
    /**
     * Generate the encrypted URL for viewing a sector's details.
     *
     * @param int $sectorId
     * @param int|string|null $commId Optional commitment id for deep link
     * @return string
     */
    function sector_view_url($sectorId, $commId = null)
    {
        $payload = json_encode([
            'id' => (int)$sectorId,
            'comm_id' => $commId,
        ]);
        $encrypted = app('encrypter')->encrypt($payload);

        return route('sectors.view.encrypted', ['e' => rawurlencode($encrypted)]);
    }
}

if (!function_exists('commitment_deliverables_url')) {
    /**
     * Generate the encrypted URL for a commitment's deliverables page.
     *
     * @param int $commitmentId
     * @return string
     */
    function commitment_deliverables_url($commitmentId)
    {
        $payload = json_encode(['id' => (int)$commitmentId]);
        $encrypted = app('encrypter')->encrypt($payload);

        return route('commitments.deliverables.encrypted', ['e' => rawurlencode($encrypted)]);
    }
}

if (!function_exists('deliverable_kpis_url')) {
    /**
     * Generate the encrypted URL for a deliverable's KPIs page.
     *
     * @param int $deliverableId
     * @return string
     */
    function deliverable_kpis_url($deliverableId)
    {
        $payload = json_encode(['id' => (int) $deliverableId]);
        $encrypted = app('encrypter')->encrypt($payload);

        return route('deliverable.kpis.encrypted', ['e' => rawurlencode($encrypted)]);
    }
}
