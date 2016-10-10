<?php

namespace Chassis\Integration\Eloquent;

use Illuminate\Database\Eloquent\Model;

class BotStorage extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bot-storage';
    
    protected $guarded = [];
    
    /**
     * Get the user.
     */
    public function user()
    {
        // TODO: read Config
        return $this->belongsTo('App\User');
    }
}
