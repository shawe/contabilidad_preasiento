<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014-2016  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('asiento.php');
require_model('concepto_partida.php');
require_model('cuenta_banco.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('impuesto.php');
require_model('partida.php');
require_model('regularizacion_iva.php');
require_model('subcuenta.php');
require_model('preasiento.php');

class contabilidad_preasiento extends fs_controller
{
   public $asiento;
   public $concepto;
   public $cuenta_banco;
   public $divisa;
   public $ejercicio;
   public $impuesto;
   public $lineas;
   public $resultados;
   public $subcuenta;
   public $preasiento;
   public $preasientos;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Preasientos', 'contabilidad');
   }
   
   protected function private_core()
   {
      $this->ppage = $this->page->get('contabilidad_asientos');
      
      $this->asiento = new asiento();
      $this->concepto = new concepto_partida();
      $this->cuenta_banco = new cuenta_banco();
      $this->divisa = new divisa();
      $this->ejercicio = new ejercicio();
      $this->impuesto = new impuesto();
      $this->lineas = array();
      $this->resultados = array();
      $this->subcuenta = new subcuenta();
      $this->preasiento = new preasiento();
      $this->preasientos = $this->preasiento->all();
      
      if( isset($_POST['NPreAsiento']))
      {
          $this->preasiento = new preasiento($_POST['NPreAsiento']);
          if ( $this->preasiento->save() )
          {
              echo ("Ok");
              return;
          }
      }
      else if( isset($_POST['BorrarPreAsiento']))
      {
          $this->preasiento = new preasiento($_POST['BorrarPreAsiento']);
          if ( $this->preasiento->delete() )
          {
              echo ("Ok");
              return;
          }
      }

      else if( isset($_POST['fecha']) AND isset($_POST['query']) )
      {
         $this->new_search();
      }
      
   }
   
   private function new_search()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/contabilidad_nuevo_asiento';
      
      $eje0 = $this->ejercicio->get_by_fecha($_POST['fecha']);
      if($eje0)
      {
         $this->resultados = $this->subcuenta->search_by_ejercicio($eje0->codejercicio, $this->query);
      }
      else
      {
         $this->resultados = array();
         $this->new_error_msg('Ningún ejercicio encontrado para la fecha '.$_POST['fecha']);
      }
   }
}
