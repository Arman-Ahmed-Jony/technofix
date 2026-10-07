<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\SavedBuild;
use App\Support\PcBuilder\Compatibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** My account → Saved PC builds (saved from the PC Builder page). */
class SavedBuildController extends Controller
{
    public function index(Request $request, Compatibility $compatibility): View
    {
        $builds = $request->user('customer')->savedBuilds()->get()->map(function (SavedBuild $saved) use ($compatibility) {
            $build = $saved->build();

            return [
                'model' => $saved,
                'parts' => collect($build->items)->sum('qty'),
                'total' => $build->subtotal(),
                'problems' => count(array_filter($compatibility->issues($build), fn ($i) => $i['level'] === 'error')),
                'names' => collect($build->items)->map(fn ($item) => $item['product']->name)->take(3)->values(),
            ];
        });

        return view('account.builds', ['builds' => $builds]);
    }

    public function destroy(Request $request, int $build): RedirectResponse
    {
        $request->user('customer')->savedBuilds()->findOrFail($build)->delete();

        return redirect()->route('account.builds')->with('status', 'Build deleted.');
    }
}
