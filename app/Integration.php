<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'service_name', 'account_id', 'account_data',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'account_data' => 'array',
    ];

    /**
     * Get the user associated with the integration.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
