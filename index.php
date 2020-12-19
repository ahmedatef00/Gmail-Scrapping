<?php
class Scrap
{

    public function __construct()
    {
        $this->include();
    }

    private function include()
    {
        require __DIR__ . '/vendor/autoload.php';
        include 'connection.php';
    }

    public function go()
    {
        $conn = new Connection();
        if ($conn->is_connected()) {
            //do some stuff
            require_once('gmail.php');
            $gmail=new Gmail($conn->get_client());
            return $gmail->read_bodies();
        } else {
            return $conn->get_unauthnticated_data();
        }
    }
}

$scrap = new Scrap();
echo $scrap->go();

// if (php_sapi_name() != 'cli') {
//     throw new Exception('This application must be run on the command line.');
// }

