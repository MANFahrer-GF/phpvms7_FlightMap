<?php

namespace Modules\FlightMap\Http\Controllers\Api;

use App\Contracts\Controller;
use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\Enums\PirepState;
use App\Models\Enums\UserState;
use App\Models\Pirep;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapController extends Controller
{
    /**
     * Mode 1: the logged-in pilot's accepted flights as straight A->B lines.
     */
    public function myFlights(Request $request): JsonResponse
    {
        return $this->routeLines((int) auth()->id());
    }

    /**
     * Mode 2: all pilots' accepted flights, optionally filtered to one pilot.
     */
    public function allFlights(Request $request): JsonResponse
    {
        $pilot = $request->get('pilot');

        return $this->routeLines($pilot ? (int) $pilot : null);
    }

    /**
     * Build aggregated dpt->arr geodesic lines. Identical routes are collapsed
     * into one line carrying a flown-count (the frontend scales line weight by it).
     */
    protected function routeLines(?int $userId): JsonResponse
    {
        // Pirep uses SoftDeletes -> the global scope already excludes deleted rows.
        $q = Pirep::where('state', PirepState::ACCEPTED)
            ->whereNotNull('dpt_airport_id')
            ->whereNotNull('arr_airport_id');

        if ($userId) {
            $q->where('user_id', $userId);
        }

        $rows = $q->select('dpt_airport_id', 'arr_airport_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('dpt_airport_id', 'arr_airport_id')
            ->get();

        $aptIds = $rows->pluck('dpt_airport_id')
            ->merge($rows->pluck('arr_airport_id'))
            ->filter()
            ->unique()
            ->values();

        $apts = Airport::whereIn('id', $aptIds)
            ->get(['id', 'icao', 'name', 'lat', 'lon'])
            ->keyBy('id');

        $lines = [];
        $usedApts = [];

        foreach ($rows as $r) {
            $d = $apts->get($r->dpt_airport_id);
            $a = $apts->get($r->arr_airport_id);

            if (!$d || !$a || $d->lat === null || $a->lat === null) {
                continue;
            }
            if ($d->id === $a->id) {
                continue; // same airport (pattern work) -> no line
            }

            $lines[] = [
                'from'  => [(float) $d->lat, (float) $d->lon],
                'to'    => [(float) $a->lat, (float) $a->lon],
                'dep'   => $d->icao,
                'arr'   => $a->icao,
                'count' => (int) $r->cnt,
            ];

            $usedApts[$d->id] = ['icao' => $d->icao, 'name' => $d->name, 'lat' => (float) $d->lat, 'lon' => (float) $d->lon];
            $usedApts[$a->id] = ['icao' => $a->icao, 'name' => $a->name, 'lat' => (float) $a->lat, 'lon' => (float) $a->lon];
        }

        return response()->json([
            'lines'    => $lines,
            'airports' => array_values($usedApts),
        ]);
    }

    /**
     * Mode 3: where all aircraft currently are, grouped per airport.
     */
    public function aircraft(Request $request): JsonResponse
    {
        $list = Aircraft::with('subfleet.airline')
            ->whereNotNull('airport_id')
            ->get(['id', 'airport_id', 'registration', 'name', 'icao', 'subfleet_id']);

        $byApt = $list->groupBy('airport_id');

        $apts = Airport::whereIn('id', $byApt->keys()->all())
            ->get(['id', 'icao', 'name', 'lat', 'lon'])
            ->keyBy('id');

        $out = [];

        foreach ($byApt as $aptId => $acs) {
            $apt = $apts->get($aptId);
            if (!$apt || $apt->lat === null) {
                continue;
            }

            $out[] = [
                'icao'     => $apt->icao,
                'name'     => $apt->name,
                'lat'      => (float) $apt->lat,
                'lon'      => (float) $apt->lon,
                'count'    => $acs->count(),
                'aircraft' => $acs->sortBy('registration')->map(function ($x) {
                    $airline = optional($x->subfleet)->airline;

                    return [
                        'reg'          => $x->registration,
                        'type'         => $x->icao,
                        'name'         => $x->name,
                        'airline'      => $airline?->name,
                        'airline_icao' => $airline?->icao,
                        'airline_logo' => $airline?->logo,
                    ];
                })->values(),
            ];
        }

        // Biggest clusters first so they render on top.
        usort($out, fn ($a, $b) => $b['count'] <=> $a['count']);

        return response()->json(['data' => $out]);
    }

    /**
     * Pilot list for the "all flights" filter — only ACTIVE pilots with accepted
     * flights (deleted pilots are excluded by the User SoftDeletes scope; pending,
     * rejected, on-leave and suspended pilots are filtered out via the state).
     */
    public function pilots(Request $request): JsonResponse
    {
        $ids = Pirep::where('state', PirepState::ACCEPTED)
            ->distinct()
            ->pluck('user_id');

        $users = User::whereIn('id', $ids)
            ->where('state', UserState::ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'data' => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
        ]);
    }
}
