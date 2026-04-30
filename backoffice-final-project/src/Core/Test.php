<?php 

namespace App\Core;

class Test{

    private string $message;

    public function __construct(string $message) { $this->message = $message; }

    public function hola(): string { return $this->message; }

}

?>