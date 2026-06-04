<?php
//activamos almacenamiento en el buffer
ob_start();
session_start();
if (!isset($_SESSION['nombre'])) {
  header("Location: login.php");
}else{


require 'header.php';

if ($_SESSION['implementacion']==1) {

 ?>
 

 
 </style>
    <div class="content-wrapper">
    <!-- Main content -->
    <section class="content">

      <!-- Default box -->
      <div class="row">
        <div class="col-md-12">
      <div class="box">
<div class="box-header with-border">
  <h1 class="box-title">Implementación&nbsp;&nbsp;&nbsp; </h1>
  <div class="box-tools pull-right">
    
  </div>
</div>

<style>
#listadoregistros tbody tr td .sp{
  
   
  
}
</style>
<!--box-header-->
<!--centro-->
<!--SELECT `id`, `zona`, `cliente`, `id_usuario`, `fecha`, `estado`, `etapa`, `comentario`, `flag` FROM `seguimiento` WHERE 1-->
<div class="panel-body table-responsive" id="listadoregistros">
  <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover">
    <thead>
      <th >#</th>
      <th>Fecha</th>
	  <th>Zona</th>
      <th>Cliente</th>
      <th>Producto</th>
      <th>Etapa</th>
	  <th>Bloqueo</th>
	  <th>Comentario</th>
	   <th>Modificado</th>
	   <th>Dias</th>
	  <th>Estado</th>
    </thead>
    <tbody>
    </tbody>

  </table>
</div>
<div class="panel-body" style="height: 580px;display: none;" id="formularioregistros"  >
  <form action="" name="formulario" id="formulario" method="POST">
  
   <input class="form-control" type="hidden" name="idventa" id="idventa">
   
    <div class="form-group col-lg-8 col-md-8 col-xs-12">
      <label for="">Cliente(*):</label>
      <input class="form-control" type="text" name="cliente" id="cliente" required>
	  
    </div>
      <div class="form-group col-lg-4 col-md-4 col-xs-12">
      <label for="">Zona(*): </label>
       <select name="zona" id="zona" class="form-control selectpicker" required>
	   <option value=""></option>
       <option value="CENTRO&OESTE">CENTRO&OESTE</option>
       <option value="ESTE">ESTE</option>
       <option value="NOA">NOA</option>
	   <option value="PBA&SUR">PBA&SUR</option>
	   <option value="NEA">NEA</option>
	   <option value="GBA">GBA</option>
     </select>
    </div>
     <div class="form-group col-lg-6 col-md-6 col-xs-12">
      <label for="">Producto: </label>
     <select name="producto" id="producto" class="form-control selectpicker" required>
	 <option value=""></option>
       <option value="PIC+GPS+LIDER">PIC+GPS+LIDER</option>
       <option value="PIC+GPS">PIC+GPS</option>
       <option value="PIC">PIC</option>
	   <option value="PIC+GPS+LIDER+CAMIONES">PIC+GPS+LIDER+CAMIONES</option>
     </select>
    </div>
	
 <div class="form-group col-lg-6 col-md-6 col-xs-12">
      <label for="">Responsable: </label>
     <select name="responsable" id="responsable" class="form-control selectpicker" required>
	 <option value=""></option>
    
     </select>
 </div>
 
  <div class="form-group col-lg-6 col-md-6 col-xs-12">
      <label for="">Etapa: </label>
     <select name="etapa" id="etapa" class="form-control selectpicker" required>
	 <option value=""></option>
    
     </select>
 </div>
   <div class="form-group col-lg-6 col-md-6 col-xs-12">
      <label for="">Bloqueante: </label>
     <select name="bloqueante" id="bloqueante" class="form-control selectpicker" required>
	  <option value=""></option>
	  <option value="SIN-BLOQUEO">SIN-BLOQUEO</option>
	 <option value="CLIENTE">CLIENTE</option>
	  <option value="ERP">ERP</option>
	  <option value="AXUM">AXUM</option>
	  <option value="AXUM">MULTINACIONAL</option>
    
     </select>
 </div>
 

    <div class="form-group col-lg-4 col-md-4 col-xs-12">
      <label for="">Equipos: </label>
     <select name="equipo" id="equipo" class="form-control selectpicker" required>
	 <option value=""></option>
     <option value="SI">SI</option>
	 <option value="NO">NO</option>
     </select>
 </div>
    <div class="form-group col-lg-4 col-md-4 col-xs-12">
      <label for="">Integración: </label>
      <select name="integracion" id="integracion" class="form-control selectpicker" required>
	 <option value=""></option>
     <option value="SI">SI</option>
	 <option value="NO">NO</option>
     </select>
 </div>
    <div class="form-group col-lg-4 col-md-4 col-xs-12">
      <label for="">Configuración: </label>
      <select name="configuracion" id="configuracion" class="form-control selectpicker" required>
	 <option value=""></option>
     <option value="SI">SI</option>
	 <option value="NO">NO</option>
     </select>
 </div>
 
    <div class="form-group col-lg-4 col-md-4 col-xs-12">
      <label for="">Capacitación: </label>
     <select name="capacitacion" id="capacitacion" class="form-control selectpicker" required>
	 <option value=""></option>
    
     </select>
 </div>
 
  <div class="form-group col-lg-12 col-md-12 col-xs-12"">
    <label for="">Comentario</label>
    <textarea class="form-control"   name="comentario" id="comentario" rows="3" required ></textarea>
  </div>
  
    <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
      <button class="btn btn-primary" type="submit" id="btnGuardar"><i class="fa fa-save"></i>  Guardar</button>
      <button class="btn btn-danger" onclick="cancelarform()" type="button" id="btnCancelar"><i class="fa fa-arrow-circle-left"></i> Cancelar</button>
    </div>
  </form>
</div>
<!--fin centro-->
      </div>
      </div>
      </div>
      <!-- /.box -->

    </section>
    <!-- /.content -->
  </div>

 
<?php 
}else{
 require 'noacceso.php'; 
}

require 'footer.php';
 ?>
 <script src="scripts/implementacion.js?t=<?php echo time(); ?>"></script>
 <?php 
}

ob_end_flush();
  ?>

