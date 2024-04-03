<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class PatientController extends Controller
{
    private $queueName = "PostRoomToPatient";

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function get($id){   
        $room = \App\Models\Patient::where('id', '=', $id)->first();        
        return response()->json($room);
    }

    public function post(Request $request){        
        $parameters = [];
        $parameters['name'] = $request->input('name');
        $parameters['address'] = $request->input('address');
        $parameters['phone'] = $request->input('phone');
        $patient = \App\Models\Patient::create($parameters);

        if (!empty($request->input('room_id'))) {
            $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
            $channel = $connection->channel();
    
            $channel->queue_declare($this->queueName, false, false, false, false);
    
            $payload = [];
            $payload['url'] = 'http://localhost:8000/api/roompatient';
            $payload['payload'] = [
                'room_id' => $request->input('room_id'),
                'patient_id' => $patient->id,
                'admit_date' => date("Y-m-d h:i:s")
            ];
            $msg = new AMQPMessage(json_encode($payload), ['content_type' => 'application/json']);
            $channel->basic_publish($msg, '', $this->queueName);
    
            $channel->close();
            $connection->close();                

            //create job
            $job = (new \App\Jobs\PostRoomPatientJob($this->queueName));
            dispatch($job);
        }

        return response()->json($patient);        
    }
    
    public function put(Request $request, $id){
        $room = \App\Models\Patient::find($id);
        // $room->number = $request->input('number');
        // $room->type = $request->input('type');
        // $room->availibility = $request->input('availibility');
        // $room->hospital_id = $request->input('hospital_id');
        // $room->save();
 
        return response()->json($room);        
    }
}
