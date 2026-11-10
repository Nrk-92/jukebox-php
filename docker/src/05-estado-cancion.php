<?php
$duration = 240; 

if ($duration === 0) {
    echo "Sin duración";
} elseif ($duration > 0 && $duration <= 240) {
    echo "Hasta cuatro minutos";
} else {
    echo "Más de cuatro minutos";
}