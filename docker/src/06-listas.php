<?php
echo "<h3>Lista con FOR:</h3><ul>";
for ($i = 1; $i <= 4; $i++) {
    echo "<li>Posición $i</li>";
}
echo "</ul>";

echo "<h3>Lista con WHILE:</h3><ul>";
$j = 1;
while ($j <= 4) {
    echo "<li>Posición $j</li>";
    $j++;
}
echo "</ul>";