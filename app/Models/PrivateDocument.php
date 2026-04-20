<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivateDocument extends Model
{
    use HasFactory;

    protected $table = 'private_documents';

    protected $fillable = [
        'user_id',
        'document_type',
        'final_report_base64',
        'document_text',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
        ];
    }

    // Relacionamentos
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}








