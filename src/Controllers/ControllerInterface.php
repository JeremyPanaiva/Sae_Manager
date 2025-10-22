<?php
namespace Controllers;

interface ControllerInterface {
    function control() ; 
    static function support(string $chemin, string $method) : bool; 
}