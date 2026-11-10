<?php
function duracionLegible(int $total): string
{
    $minutes = intdiv($total, 60);
    $seconds = $total % 60;
    return "$minutes minutos y $seconds segundos";
}