<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RoomLogbook;
use App\Models\InstrumentLogbook;

class LogbookController extends Controller
{
    public function indexRoom(Request $request)
    {
        $workshopsList = DB::table('stage_production')->pluck('workshop_code')->unique();
        $workshop = $request->input('workshop', $workshopsList->first());

        // Fetch rooms for the selected workshop
        $rooms = DB::connection('pms')->table('room')
                    ->where('deparment_code', $workshop)
                    ->get()
                    ->keyBy('id');

        $roomIds = $rooms->keys()->toArray();

        $logbooks = RoomLogbook::whereIn('room_id', $roomIds)
                        ->orderBy('start_time', 'desc')
                        ->paginate(20);

        return view('pages.logbooks.room', compact('logbooks', 'rooms', 'workshopsList', 'workshop'));
    }

    public function indexInstrument(Request $request)
    {
        $workshopsList = DB::table('stage_production')->pluck('workshop_code')->unique();
        $workshop = $request->input('workshop', $workshopsList->first());

        // Get instruments for the workshop
        $instruments = DB::table('instrument')
                        ->where('department_code', $workshop)
                        ->get();
                        
        $instrumentCodes = $instruments->pluck('code')->toArray();
        $instrumentIds = $instruments->pluck('id')->toArray();
        
        // For testing purposes, always include the seeded dummy instruments regardless of workshop 
        // just so the view isn't empty when testing
        $instrumentCodes = array_merge($instrumentCodes, ['MAYCAN-01', 'MAYTRON-02', 'MAYBAO-01']);

        // We fetch the dummy instruments info as well
        $dummyInstruments = DB::table('instrument')->whereIn('code', ['MAYCAN-01', 'MAYTRON-02', 'MAYBAO-01'])->get();
        
        $instrumentsCollection = collect();
        foreach ($instruments as $inst) {
            $instrumentsCollection->put((string)$inst->id, $inst);
            $instrumentsCollection->put($inst->code, $inst);
        }
        foreach ($dummyInstruments as $inst) {
            $instrumentsCollection->put((string)$inst->id, $inst);
            $instrumentsCollection->put($inst->code, $inst);
        }

        $searchKeys = array_merge($instrumentCodes, $instrumentIds);

        $logbooks = InstrumentLogbook::whereIn('instrument_id', $searchKeys)
                        ->orderBy('start_time', 'desc')
                        ->paginate(20);

        return view('pages.logbooks.instrument', [
            'logbooks' => $logbooks, 
            'instruments' => $instrumentsCollection, 
            'workshopsList' => $workshopsList, 
            'workshop' => $workshop
        ]);
    }
}
