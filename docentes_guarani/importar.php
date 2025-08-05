<?php

CONST CSV_FILE_GUARANI = 'Docente_Guarani.csv';
CONST DELIMITER = ','; 
$LINES = str_repeat('-', 80)."\n";


require_once "./paso1.php"; // verificar y crear tabla si no existe
require_once "./paso2.php"; // 
require_once "./paso3.php"; // separa los registros con dos o mas docentes
require_once "./paso4.php"; // limpieza de registros
require_once "./paso5.php"; 
 
 


// Cerrar conexión
$conn = null;

