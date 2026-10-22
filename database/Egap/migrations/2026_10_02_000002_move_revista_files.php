<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms_page_articles')) {
            return;
        }

        $newDirectory = public_path('arquivos/revista');
        File::ensureDirectoryExists($newDirectory);

        DB::table('cms_page_articles')
            ->whereNotNull('pdf_path')
            ->orderBy('id')
            ->chunkById(100, function ($articles) use ($newDirectory): void {
                foreach ($articles as $article) {
                    $path = str_replace('\\', '/', $article->pdf_path);

                    if (Str::startsWith($path, ['http://', 'https://'])) {
                        continue;
                    }

                    $fileName = basename($path);
                    $targetPath = $newDirectory . DIRECTORY_SEPARATOR . $fileName;
                    $sourcePaths = [
                        public_path('revista/' . $path),
                        storage_path('app/public/' . $path),
                        public_path('storage/' . $path),
                        public_path($path),
                    ];

                    foreach ($sourcePaths as $sourcePath) {
                        if (File::exists($targetPath) || ! File::exists($sourcePath)) {
                            continue;
                        }

                        File::copy($sourcePath, $targetPath);
                        break;
                    }

                    if (File::exists($targetPath) && $article->pdf_path !== $fileName) {
                        DB::table('cms_page_articles')
                            ->where('id', $article->id)
                            ->update(['pdf_path' => $fileName]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Arquivos migrados não são removidos automaticamente.
    }
};
