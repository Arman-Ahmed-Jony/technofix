<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/*
| Images uploaded in the admin before 2026-09-30 landed on the private "local" disk (Filament's default), so the
| website couldn't show them. Move them — originals and generated conversions — to the public media disk.
*/
return new class extends Migration
{
    public function up(): void
    {
        $target = config('media-library.disk_name', 'public');

        foreach (DB::table('media')->where('disk', 'local')->orWhere('conversions_disk', 'local')->get() as $media) {
            $from = Storage::disk('local');
            $to = Storage::disk($target);
            $folder = (string) $media->id; // DefaultPathGenerator: "{id}/" and "{id}/conversions/"

            foreach ($from->allFiles($folder) as $file) {
                $to->put($file, $from->get($file), 'public');
            }

            $from->deleteDirectory($folder);

            DB::table('media')->where('id', $media->id)->update(['disk' => $target, 'conversions_disk' => $target]);
        }
    }

    public function down(): void
    {
        // Nothing to undo: public is where these files belong.
    }
};
