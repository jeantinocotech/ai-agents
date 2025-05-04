<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SeedDefaultFiles extends Command
{
    protected $signature = 'app:seed-default-files';
    protected $description = 'Copia arquivos padrão de imagem e vídeo para o storage';

    public function handle()
    {
        $this->info('Copiando arquivos padrão para storage...');
        
        // Criar diretórios se não existirem
        Storage::disk('public')->makeDirectory('agents/images');
        Storage::disk('public')->makeDirectory('agents/videos');
        
        // Copiar imagens padrão
        $defaultImagePath = resource_path('defaults/images');
        if (File::isDirectory($defaultImagePath)) {
            $images = File::files($defaultImagePath);
            foreach ($images as $image) {
                $filename = $image->getFilename();
                $destination = 'agents/images/' . $filename;
                
                if (!Storage::disk('public')->exists($destination)) {
                    Storage::disk('public')->put(
                        $destination,
                        File::get($image->getPathname())
                    );
                    $this->info("Copiado: $filename");
                }
            }
        } else {
            $this->warn("Diretório de imagens padrão não encontrado. Crie o diretório $defaultImagePath");
        }
        
        // Copiar vídeos padrão
        $defaultVideoPath = resource_path('defaults/videos');
        if (File::isDirectory($defaultVideoPath)) {
            $videos = File::files($defaultVideoPath);
            foreach ($videos as $video) {
                $filename = $video->getFilename();
                $destination = 'agents/videos/' . $filename;
                
                if (!Storage::disk('public')->exists($destination)) {
                    Storage::disk('public')->put(
                        $destination,
                        File::get($video->getPathname())
                    );
                    $this->info("Copiado: $filename");
                }
            }
        } else {
            $this->warn("Diretório de vídeos padrão não encontrado. Crie o diretório $defaultVideoPath");
        }
        
        $this->info('Arquivos padrão copiados com sucesso!');
    }
}
