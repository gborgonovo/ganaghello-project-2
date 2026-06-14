<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'identicon'];
    protected $hidden   = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function tasks()       { return $this->hasMany(Task::class); }
    public function assignedTasks() { return $this->hasMany(Task::class, 'assigned_to'); }
    public function collaboratingTasks() { return $this->belongsToMany(Task::class, 'task_collaborators'); }
    public function goals()       { return $this->hasMany(Goal::class); }
    public function entries()     { return $this->hasMany(Entry::class); }
    public function posts()       { return $this->hasMany(Post::class); }
    public function media()       { return $this->hasMany(Media::class); }
    public function notes()       { return $this->hasMany(Note::class); }
    public function inspirations(){ return $this->hasMany(Inspiration::class); }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
