<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collaborator extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'cpf',
        'city',
        'state',
        'user_id',
    ];

    /**
     * Get the user that owns the collaborator.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set the CPF attribute (sanitize)
     */
    public function setCpfAttribute($value)
    {
        $this->attributes['cpf'] = preg_replace('/\D/', '', $value);
    }

    /**
     * Get the CPF attribute formatted
     */
    public function getFormattedCpfAttribute()
    {
        $cpf = $this->cpf;

        return substr($cpf, 0, 3).'.'.substr($cpf, 3, 3).'.'.substr($cpf, 6, 3).'-'.substr($cpf, 9, 2);
    }
}
