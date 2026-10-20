<?php

namespace App\Models\Sistema;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaSistema extends Model
{
    protected $table = 'sys_eventos_auditoria';

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'datos' => 'array',
        'created_at' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
