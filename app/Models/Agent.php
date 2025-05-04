<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image_path',
        'video_path',
        'api_key',
        'model_type',
        'price',
        'organization',
        'project_id',
        'system_prompt',
    ];
    
    /**
     * Os atributos que devem ser ocultados para arrays.
     *
     * @var array
     */
    protected $hidden = [
        'api_key',
    ];

     /**
     * Obter a URL completa do vídeo do YouTube.
     *
     * @return string
     */
    public function getYoutubeUrlAttribute()
    {
        return 'https://www.youtube.com/embed/' . $this->youtube_video_id;
    }
        
    public function steps()
    {
        return $this->hasMany(AgentStep::class)->orderBy('step_order');
    }

}
