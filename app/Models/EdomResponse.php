<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdomResponse extends Model
{
    protected $table = 'edom_response';

    protected $fillable = [
        'edom_period_id',
        'edom_setting_id',
        'siakad_idmahasiswa',
        'siakad_idmatakuliah',
        'siakad_idtawarmatakuliahdetail',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(EdomPeriod::class, 'edom_period_id');
    }

    public function settingEdom()
    {
        return $this->belongsTo(SettingEdom::class, 'edom_setting_id');
    }

    public function edom()
    {
        return $this->settingEdom();
    }

    public function details()
    {
        return $this->hasMany(EdomResponseDetail::class, 'edom_response_id');
    }

    public function answers()
    {
        return $this->details();
    }
}
