<?php

namespace App\Jobs;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class PostDoctorPatientJob extends Job
{
    private $queueName;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($queueName)
    {
        $this->queueName = $queueName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->attempts() >= 0) {
            $connection = new AMQPStreamConnection(env('DB_HOST'), 5672, 'guest', 'guest');
            $channel = $connection->channel();

            $channel->queue_declare($this->queueName, false, false, false, false);

            echo " [*] Waiting for messages. To exit press CTRL+C\n";

            $callback = function ($msg) {
                //consumer of rabbitmq
                echo ' [x] Received ', $msg->body, "\n";
                $body = json_decode($msg->body, true);
                echo "Body of message";
                print_r($body);
                $payload = $body['payload'];
                $url = $body['url'];
                
                $ch = curl_init();

                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                // Receive server response ...
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $output = curl_exec($ch);
                $status = curl_getinfo($ch);
                $error = curl_error($ch);
                print_r($error);

                curl_close($ch);            
            };
            
            $channel->basic_consume($this->queueName, '', false, true, false, false, $callback);
            
            try {
                $channel->consume();
            } catch (\Throwable $exception) {
                echo $exception->getMessage();
            }
        }
    }
}
