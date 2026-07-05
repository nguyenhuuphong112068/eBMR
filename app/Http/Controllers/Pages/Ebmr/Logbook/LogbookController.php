<?php

namespace App\Http\Controllers\Pages\Ebmr\Logbook;

use App\Http\Controllers\Controller;

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
                    ->where('stage_code', '<=', 7)
                    ->orderBy('stage_code')
                    ->get()
                    ->groupBy('stage');

        return view('pages.logbooks.room_list', compact('rooms', 'workshopsList', 'workshop'));
    }

    public function showRoom(Request $request, $room_id)
    {
        $room = DB::connection('pms')->table('room')->where('id', $room_id)->first();
        if (!$room) abort(404, 'Không tìm thấy phòng.');

        $logbooks = RoomLogbook::where('room_id', $room_id)
                        ->orderBy('start_time', 'desc')
                        ->paginate(20);

        $usersMap = DB::table('user_management')->pluck('fullName', 'id');

        return view('pages.logbooks.room', compact('logbooks', 'room', 'usersMap'));
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

        return view('pages.logbooks.instrument_list', [
            'instruments' => $instrumentsCollection->unique('id'), 
            'workshopsList' => $workshopsList, 
            'workshop' => $workshop
        ]);
    }

    public function showInstrument(Request $request, $instrument_id)
    {
        $instrument = DB::table('instrument')->where('id', $instrument_id)->orWhere('code', $instrument_id)->first();
        if (!$instrument) abort(404, 'Không tìm thấy thiết bị.');

        $logbooks = InstrumentLogbook::where('instrument_id', $instrument->id)
                        ->orWhere('instrument_id', $instrument->code)
                        ->orderBy('start_time', 'desc')
                        ->paginate(20);

        $usersMap = DB::table('user_management')->pluck('fullName', 'id');

        return view('pages.logbooks.instrument', compact('logbooks', 'instrument', 'usersMap'));
    }
}
