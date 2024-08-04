<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class location extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'latitude' => 'double',
            'longitude' => 'double',
            'accuracy' => 'double',
            'altitude' => 'double',
            'speed' => 'double',
            'speed_accuracy' => 'double',
            'heading' => 'double',
            'time' => 'double',
            'is_mock' => 'integer',
            'vertical_accuracy' => 'double',
            'heading_accuracy' => 'double',
            'elapsed_realtime_nanos' => 'double',
            'elapsed_realtime_uncertainty_nanos' => 'double',
            'satellite_number' => 'integer',
            'provider' => 'string',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
