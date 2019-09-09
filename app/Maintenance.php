<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    protected $fillable = [
        'perform_on',
        'protocolUUID',
        'isWarrantyEvent',
        'protocolNumber',
        'status',
        'explanation'
    ];

    public function assets()
    {
        return $this->belongsToMany(Asset::class, 'asset_maintenance','asset_id','id');
    }

    public function generateRMANumber()
    {
        $lastMaintenanceID = \App\Maintenance::orderBy('id', 'desc')->first()->id;
        $leadZeroPatern = str_pad($lastMaintenanceID, 10, 0, STR_PAD_LEFT);
        $completeMaintenanceIdForRecord = 'RMA-' . $leadZeroPatern;

        return $completeMaintenanceIdForRecord;
    }
}
