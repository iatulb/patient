<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class PatientController extends Controller
{
    private $queuePostRoomToPatient = "PostRoomToPatient";
    private $queuePostPatientToDoctor = "PostPatientToDoctor";

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
            $connection = new AMQPStreamConnection(env('DB_HOST'), 5672, 'guest', 'guest');
            $channel = $connection->channel();
    
            $channel->queue_declare($this->queuePostRoomToPatient, false, false, false, false);
    
            $payload = [];
            $payload['url'] = 'http://'. env('DB_HOST') .':8001/api/roompatient';
            $payload['payload'] = [
                'room_id' => $request->input('room_id'),
                'patient_id' => $patient->id,
                'admit_date' => date("Y-m-d h:i:s")
            ];
            $msg = new AMQPMessage(json_encode($payload), ['content_type' => 'application/json']);
            $channel->basic_publish($msg, '', $this->queuePostRoomToPatient);
    
            $channel->close();
            $connection->close();                

            //create job
            $job = (new \App\Jobs\PostRoomPatientJob($this->queuePostRoomToPatient));
            dispatch($job);
        }

        if (!empty($request->input('doctor_id'))) {            
            $connection = new AMQPStreamConnection(env('DB_HOST'), 5672, 'guest', 'guest');
            $channel = $connection->channel();
    
            $channel->queue_declare($this->queuePostRoomToPatient, false, false, false, false);
    
            $payload = [];
            $payload['url'] = 'http://'. env('DB_HOST') .':8003/api/doctorpatient';
            $payload['payload'] = [
                'doctor_id' => $request->input('doctor_id'),
                'patient_id' => $patient->id
            ];
            $msg = new AMQPMessage(json_encode($payload), ['content_type' => 'application/json']);
            $channel->basic_publish($msg, '', $this->queuePostRoomToPatient);
    
            $channel->close();
            $connection->close();                

            //create job
            $job = (new \App\Jobs\PostDoctorPatientJob($this->queuePostRoomToPatient));
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
