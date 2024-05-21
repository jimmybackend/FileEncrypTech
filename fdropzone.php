<?php include "main00.php"; ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Esforzados</title>
<?php include "main0.php"; ?>
<!-- Agrega la librería de Dropzone -->
    <!-- Incluye la hoja de estilos de Dropzone desde el CDN -->
    <link href="https://cdn.jsdelivr.net/npm/dropzone@5.9.2/dist/min/dropzone.min.css" rel="stylesheet">
    <!-- Incluye la biblioteca de Dropzone desde el CDN -->
    <script src="https://cdn.jsdelivr.net/npm/dropzone@5.9.2/dist/min/dropzone.min.js"></script>
</head>
<body class="layout-column">
<?php include "main01.php"; ?>
<?php include "main5.php"; ?>
	<div id="content" class="flex ">
<div class="page-container" id="page-container">
	<div class="page-title padding pb-0 ">
	<div class="table-responsive">

<div class="container">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Dropzone Binario 512Kbps</h4>
                    <h6 class="card-title">Carga primero luego se fragmenta</h6>
                    <form action="fdropzonec.php" class="dropzone white b-a b-3x b-dashed b-primary p-a rounded p-5 text-center" id="myDropzone" data-plugin="dropzone" data-option="{url: 'fdropzonec.php'}">
                        <div class="fallback">
                            <input type="file" name="file" multiple>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>




<br>
<br>
</div>
<?php
include "main6.php";
?></div>
	</div>
</div> 
</div>

<?php
include "main7.php";
?>
</body>
</html>

