<?php
/**
 * This file is part of contabilidad_preasiento
 * Copyright (C) 2014-2016  Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2017  Rafael Del Pozo Barajas
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

/**
 * Description of contabilidad_preasiento
 *
 * @author Rafael Del Pozo Barajas
 */
class contabilidad_preasiento extends fs_controller
{
    /**
     * Contiene el asiento que se va a crear.
     *
     * @var asiento
     */
    public $asiento;

    /**
     * Contiene el concepto de la partida asociado al asiento.
     *
     * @var concepto_partida
     */
    public $concepto;

    /**
     * Contiene la cuenta de banco asociada al asiento.
     *
     * @var cuenta_banco
     */
    public $cuenta_banco;

    /**
     * Contiene la divisa asociada al asiento.
     *
     * @var divisa
     */
    public $divisa;

    /**
     * Contiene el ejercicio asociado al asiento (principalmente para obtener el ejercicio según la fecha).
     *
     * @var ejercicio
     */
    public $ejercicio;

    /**
     * Contiene el impuesto asociado al asiento.
     *
     * @var impuesto
     */
    public $impuesto;

    /**
     * Contiene un listado de asientos.
     *
     * @var asiento[]
     */
    public $lineas;

    /**
     * Contiene un listado de subcuentas.
     *
     * @var subcuenta[]
     */
    public $resultados;

    /**
     * Contiene la subcuenta asociada al asiento.
     *
     * @var subcuenta
     */
    public $subcuenta;

    /**
     * Contiene un preasiento asociado al asiento.
     *
     * @var preasiento
     */
    public $preasiento;

    /**
     * Contiene un listado de preasientos.
     *
     * @var preasiento[]
     */
    public $preasientos;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        parent::__construct(__CLASS__, 'Preasientos', 'contabilidad');
    }

    /**
     * Parte privada de la clase
     */
    protected function private_core()
    {
        $this->ppage = $this->page->get('contabilidad_asientos');

        $this->asiento = new asiento();
        $this->concepto = new concepto_partida();
        $this->cuenta_banco = new cuenta_banco();
        $this->divisa = new divisa();
        $this->ejercicio = new ejercicio();
        $this->impuesto = new impuesto();
        $this->lineas = [];
        $this->resultados = [];
        $this->subcuenta = new subcuenta();
        $this->preasiento = new preasiento();
        $this->preasientos = $this->preasiento->all();

        if (isset($_POST['NPreAsiento'])) {
            $this->preasiento = new preasiento($_POST['NPreAsiento']);
            if ($this->preasiento->save()) {
                echo('Ok');
                return;
            }
        } elseif (isset($_POST['BorrarPreAsiento'])) {
            $this->preasiento = new preasiento($_POST['BorrarPreAsiento']);
            if ($this->preasiento->delete()) {
                echo('Ok');
                return;
            }
        } elseif (isset($_POST['fecha'], $_POST['query'])) {
            $this->new_search();
        }
    }

    /**
     * Busca una lista de subcuentas por AJAX
     */
    private function new_search()
    {
        /// cambiamos la plantilla HTML
        $this->template = 'ajax/contabilidad_nuevo_asiento';

        $eje0 = $this->ejercicio->get_by_fecha($_POST['fecha']);
        if ($eje0) {
            $this->resultados = $this->subcuenta->search_by_ejercicio($eje0->codejercicio, $this->query);
        } else {
            $this->resultados = [];
            $this->new_error_msg('Ningún ejercicio encontrado para la fecha ' . $_POST['fecha']);
        }
    }
}
