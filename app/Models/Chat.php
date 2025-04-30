<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $table = 'chats';
    protected $fillable = ['answer', 'question', 'user_id'];
    protected $visible = ['answer', 'question'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
