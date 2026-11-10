<?php
$duration = 185;
$minutes = intdiv($duration, 60);
$seconds = $duration % 60;
echo "La duración es: $minutes minutos y $seconds segundos.";