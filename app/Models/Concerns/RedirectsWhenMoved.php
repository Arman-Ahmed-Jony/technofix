<?php

namespace App\Models\Concerns;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Model;

/**
 * When staff change a slug, the old address redirects to the new one (301), so links shared on Facebook and pages
 * Google already knows keep working. The model says where a slug lives with pathForSlug().
 */
trait RedirectsWhenMoved
{
    /** The storefront address for a slug, e.g. "/brand/{slug}". */
    abstract public static function pathForSlug(string $slug): string;

    /** Whether addresses under this one move too (a category's "/{slug}/{brand}" pages). */
    protected static function redirectsChildren(): bool
    {
        return false;
    }

    protected static function bootRedirectsWhenMoved(): void
    {
        static::updated(function (Model $model) {
            if ($model->wasChanged('slug') && filled($old = $model->getOriginal('slug'))) {
                Redirect::moved(static::pathForSlug($old), static::pathForSlug($model->slug), static::redirectsChildren());
            }
        });
    }
}
