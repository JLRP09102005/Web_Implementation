<?php 
$nombre = "Ana";
$edad = 25;
$precio = 19.99;
$activo = true;

echo "$nombre tiene $edad años<br>";

var_dump($precio);
echo "<br>"
?>

<?php 
$colores = ["rojo", "verde", "azul"];
echo "El segundo color: " . $colores[1] . "<br>";

$alumno = [
    "nombre" => "Luis",
    "nota" => 8.5
];
print_r($alumno);
echo "<br>"
?>

<form method="POST" action="">
    <label>Tu nombre: <input type="text" name="nombre"></label>
    <button type="submit">Enviar</button>
</form>
<?php ?>