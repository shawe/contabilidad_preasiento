<?php
/**
 * This file is part of contabilidad_preasiento
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  <neorazorx@gmail.com>
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
 * Description of contabilidad_nuevo_asiento
 *
 * @author Rafael Del Pozo Barajas
 */
class contabilidad_nuevo_asiento extends fs_controller
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
        parent::__construct(__CLASS__, 'Nuevo asiento', 'contabilidad', false, false, true);
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

        if (isset($_POST['fecha'], $_POST['query'])) {
            $this->new_search();
        } elseif (isset($_POST['fecha'], $_POST['concepto'], $_POST['divisa'])) {
            if ($_POST['autonomo'] != '0') {
                if ((float) $_POST['autonomo'] > 0) {
                    $this->nuevo_asiento_autonomo();
                } else {
                    $this->new_error_msg('Importe no válido: ' . $_POST['autonomo']);
                }
            } elseif ($_POST['modelo130'] != '0') {
                if ((float) $_POST['modelo130'] > 0) {
                    $this->nuevo_asiento_modelo130();
                } else {
                    $this->new_error_msg('Importe no válido: ' . $_POST['modelo130']);
                }
            } else {
                $this->nuevo_asiento();
            }
        } elseif (isset($_GET['copy'])) {
            $this->copiar_asiento();
        } else {
            $this->check_datos_contables();
        }
    }

    /**
     * Devuelve el ejercicio para la fecha indicada.
     *
     * @param string $fecha
     *
     * @return ejercicio|bool
     */
    private function get_ejercicio($fecha)
    {
        $ejercicio = $this->ejercicio->get_by_fecha($fecha);
        if ($ejercicio) {
            $regiva0 = new regularizacion_iva();
            if ($regiva0->get_fecha_inside($fecha)) {
                $this->new_error_msg('No se puede usar la fecha ' . $_POST['fecha'] . ' porque ya hay'
                    . ' una regularización de ' . FS_IVA . ' para ese periodo.');
                $ejercicio = false;
            }
        } else {
            $this->new_error_msg('Ejercicio no encontrado.');
        }

        return $ejercicio;
    }

    /**
     * Crea un nuevo asiento.
     */
    private function nuevo_asiento()
    {
        $continuar = true;

        $eje0 = $this->get_ejercicio($_POST['fecha']);
        if (!$eje0) {
            $continuar = false;
        }

        $div0 = $this->divisa->get($_POST['divisa']);
        if (!$div0) {
            $this->new_error_msg('Divisa no encontrada.');
            $continuar = false;
        }

        if ($this->duplicated_petition($_POST['petition_id'])) {
            $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón Guardar
               y se han enviado dos peticiones. Mira en <a href="' . $this->ppage->url() . '">asientos</a>
               para ver si el asiento se ha guardado correctamente.');
            $continuar = false;
        }

        if ($continuar) {
            $this->asiento->codejercicio = $eje0->codejercicio;
            $this->asiento->idconcepto = $_POST['idconceptopar'];
            $this->asiento->concepto = $_POST['concepto'];
            $this->asiento->fecha = $_POST['fecha'];
            $this->asiento->importe = (float) $_POST['importe'];

            if ($this->asiento->save()) {
                $numlineas = (int) $_POST['numlineas'];
                for ($i = 1; $i <= $numlineas; $i++) {
                    if (isset($_POST['codsubcuenta_' . $i])) {
                        if ($_POST['codsubcuenta_' . $i] != '' && $continuar) {
                            $sub0 = $this->subcuenta->get_by_codigo($_POST['codsubcuenta_' . $i], $eje0->codejercicio);
                            if ($sub0) {
                                $partida = new partida();
                                $partida->idasiento = $this->asiento->idasiento;
                                $partida->coddivisa = $div0->coddivisa;
                                $partida->tasaconv = $div0->tasaconv;
                                $partida->idsubcuenta = $sub0->idsubcuenta;
                                $partida->codsubcuenta = $sub0->codsubcuenta;
                                $partida->debe = (float) $_POST['debe_' . $i];
                                $partida->haber = (float) $_POST['haber_' . $i];
                                $partida->idconcepto = $this->asiento->idconcepto;
                                $partida->concepto = $this->asiento->concepto;
                                $partida->documento = $this->asiento->documento;
                                $partida->tipodocumento = $this->asiento->tipodocumento;

                                if (isset($_POST['codcontrapartida_' . $i])) {
                                    if ($_POST['codcontrapartida_' . $i] != '') {
                                        $subc1 = $this->subcuenta->get_by_codigo($_POST['codcontrapartida_' . $i], $eje0->codejercicio);
                                        if ($subc1) {
                                            $partida->idcontrapartida = $subc1->idsubcuenta;
                                            $partida->codcontrapartida = $subc1->codsubcuenta;
                                            $partida->cifnif = $_POST['cifnif_' . $i];
                                            $partida->iva = (float) $_POST['iva_' . $i];
                                            $partida->baseimponible = (float) $_POST['baseimp_' . $i];
                                        } else {
                                            $this->new_error_msg('Subcuenta ' . $_POST['codcontrapartida_' . $i] . ' no encontrada.');
                                            $continuar = false;
                                        }
                                    }
                                }

                                if (!$partida->save()) {
                                    $this->new_error_msg('Imposible guardar la partida de la subcuenta ' . $_POST['codsubcuenta_' . $i] . '.');
                                    $continuar = false;
                                }
                            } else {
                                $this->new_error_msg('Subcuenta ' . $_POST['codsubcuenta_' . $i] . ' no encontrada.');
                                $continuar = false;
                            }
                        }
                    }
                }

                if ($continuar) {
                    $this->asiento->concepto = '';

                    $this->new_message("<a href='" . $this->asiento->url() . "'>Asiento</a> guardado correctamente!");
                    $this->new_change('Asiento ' . $this->asiento->numero, $this->asiento->url(), true);

                    if ($_POST['redir'] == 'TRUE') {
                        header('Location: ' . $this->asiento->url());
                    }
                } else {
                    if ($this->asiento->delete()) {
                        $this->new_error_msg("¡Error en alguna de las partidas! Se ha borrado el asiento.");
                    } else {
                        $this->new_error_msg("¡Error en alguna de las partidas! Además ha sido imposible borrar el asiento.");
                    }
                }
            } else {
                $this->new_error_msg("¡Imposible guardar el asiento!");
            }
        }
    }

    /**
     * Crea un nuevo asiento para autonomo.
     */
    private function nuevo_asiento_autonomo()
    {
        $continuar = true;

        $eje0 = $this->get_ejercicio($_POST['fecha']);
        if (!$eje0) {
            $continuar = false;
        }

        $div0 = $this->divisa->get($_POST['divisa']);
        if (!$div0) {
            $this->new_error_msg('Divisa no encontrada.');
            $continuar = false;
        }

        if ($this->duplicated_petition($_POST['petition_id'])) {
            $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón Guardar
               y se han enviado dos peticiones. Mira en <a href="' . $this->ppage->url() . '">asientos</a>
               para ver si el asiento se ha guardado correctamente.');
            $continuar = false;
        }

        if ($continuar) {
            $meses = [
                '', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio',
                'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ];

            $codcaja = '5700000000';
            if (isset($_POST['banco'])) {
                if ($_POST['banco'] != '') {
                    $codcaja = $_POST['banco'];
                }
            }

            /// asiento de cuota
            $asiento = new asiento();
            $asiento->codejercicio = $eje0->codejercicio;
            $asiento->concepto = 'Cuota de autónomo ' . $meses[(int) date('m', strtotime($_POST['fecha']))];
            $asiento->fecha = $_POST['fecha'];
            $asiento->importe = (float) $_POST['autonomo'];

            if ($asiento->save()) {
                $subc = $this->subcuenta->get_by_codigo('6420000000', $eje0->codejercicio);
                if ($subc) {
                    $partida = new partida();
                    $partida->idasiento = $asiento->idasiento;
                    $partida->coddivisa = $div0->coddivisa;
                    $partida->tasaconv = $div0->tasaconv;
                    $partida->concepto = $asiento->concepto;
                    $partida->idsubcuenta = $subc->idsubcuenta;
                    $partida->codsubcuenta = $subc->codsubcuenta;
                    $partida->debe = $asiento->importe;
                    $partida->save();
                } else {
                    $this->new_error_msg('Subcuenta 6420000000 no encontrada.');
                    $continuar = false;
                }

                $subc = $this->subcuenta->get_by_codigo('4760000000', $eje0->codejercicio);
                if ($subc) {
                    $partida = new partida();
                    $partida->idasiento = $asiento->idasiento;
                    $partida->coddivisa = $div0->coddivisa;
                    $partida->tasaconv = $div0->tasaconv;
                    $partida->concepto = $asiento->concepto;
                    $partida->idsubcuenta = $subc->idsubcuenta;
                    $partida->codsubcuenta = $subc->codsubcuenta;
                    $partida->haber = $asiento->importe;
                    $partida->save();
                } else {
                    $this->new_error_msg('Subcuenta 4760000000 no encontrada.');
                    $continuar = false;
                }

                if ($continuar) {
                    $this->new_message("<a href='" . $asiento->url() . "'>Asiento de autónomo</a> guardado correctamente!");

                    /// asiento de pago
                    $asiento = new asiento();
                    $asiento->codejercicio = $eje0->codejercicio;
                    $asiento->concepto = 'Pago de autónomo ' . $meses[(int) date('m', strtotime($_POST['fecha']))];
                    $asiento->fecha = $_POST['fecha'];
                    $asiento->importe = (float) $_POST['autonomo'];

                    if ($asiento->save()) {
                        $subc = $this->subcuenta->get_by_codigo('4760000000', $eje0->codejercicio);
                        if ($subc) {
                            $partida = new partida();
                            $partida->idasiento = $asiento->idasiento;
                            $partida->coddivisa = $div0->coddivisa;
                            $partida->tasaconv = $div0->tasaconv;
                            $partida->concepto = $asiento->concepto;
                            $partida->idsubcuenta = $subc->idsubcuenta;
                            $partida->codsubcuenta = $subc->codsubcuenta;
                            $partida->debe = $asiento->importe;
                            $partida->save();
                        }

                        $subc = $this->subcuenta->get_by_codigo($codcaja, $eje0->codejercicio);
                        if ($subc) {
                            $partida = new partida();
                            $partida->idasiento = $asiento->idasiento;
                            $partida->coddivisa = $div0->coddivisa;
                            $partida->tasaconv = $div0->tasaconv;
                            $partida->concepto = $asiento->concepto;
                            $partida->idsubcuenta = $subc->idsubcuenta;
                            $partida->codsubcuenta = $subc->codsubcuenta;
                            $partida->haber = $asiento->importe;
                            $partida->save();
                        }

                        $this->new_message("<a href='" . $asiento->url() . "'>Asiento de pago</a> guardado correctamente!");
                    }
                } else {
                    if ($asiento->delete()) {
                        $this->new_error_msg("¡Error en alguna de las partidas! Se ha borrado el asiento.");
                    } else {
                        $this->new_error_msg("¡Error en alguna de las partidas! Además ha sido imposible borrar el asiento.");
                    }
                }
            } else {
                $this->new_error_msg("¡Imposible guardar el asiento!");
            }
        }
    }

    /**
     * Crea un nuevo asiento para el modelo 130.
     */
    private function nuevo_asiento_modelo130()
    {
        $continuar = true;

        $eje0 = $this->get_ejercicio($_POST['fecha']);
        if (!$eje0) {
            $continuar = false;
        }

        $div0 = $this->divisa->get($_POST['divisa']);
        if (!$div0) {
            $this->new_error_msg('Divisa no encontrada.');
            $continuar = false;
        }

        if ($this->duplicated_petition($_POST['petition_id'])) {
            $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón Guardar
               y se han enviado dos peticiones. Mira en <a href="' . $this->ppage->url() . '">asientos</a>
               para ver si el asiento se ha guardado correctamente.');
            $continuar = false;
        }

        if ($continuar) {
            $meses = [
                '', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio',
                'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ];

            $codcaja = '5700000000';
            if (isset($_POST['banco130'])) {
                if ($_POST['banco130'] != '') {
                    $codcaja = $_POST['banco130'];
                }
            }

            /// asiento de cuota
            $asiento = new asiento();
            $asiento->codejercicio = $eje0->codejercicio;
            $asiento->concepto = 'Pago modelo 130 ' . $meses[(int) date('m', strtotime($_POST['fecha']))];
            $asiento->fecha = $_POST['fecha'];
            $asiento->importe = (float) $_POST['modelo130'];

            if ($asiento->save()) {
                $subc = $this->subcuenta->get_by_codigo('4730000000', $eje0->codejercicio);
                if ($subc) {
                    $partida = new partida();
                    $partida->idasiento = $asiento->idasiento;
                    $partida->coddivisa = $div0->coddivisa;
                    $partida->tasaconv = $div0->tasaconv;
                    $partida->concepto = $asiento->concepto;
                    $partida->idsubcuenta = $subc->idsubcuenta;
                    $partida->codsubcuenta = $subc->codsubcuenta;
                    $partida->debe = $asiento->importe;
                    $partida->save();
                } else {
                    $this->new_error_msg('Subcuenta 4730000000 no encontrada.');
                    $continuar = false;
                }

                $subc = $this->subcuenta->get_by_codigo($codcaja, $eje0->codejercicio);
                if ($subc) {
                    $partida = new partida();
                    $partida->idasiento = $asiento->idasiento;
                    $partida->coddivisa = $div0->coddivisa;
                    $partida->tasaconv = $div0->tasaconv;
                    $partida->concepto = $asiento->concepto;
                    $partida->idsubcuenta = $subc->idsubcuenta;
                    $partida->codsubcuenta = $subc->codsubcuenta;
                    $partida->haber = $asiento->importe;
                    $partida->save();
                } else {
                    $this->new_error_msg('Subcuenta ' . $codcaja . ' no encontrada.');
                    $continuar = false;
                }

                if ($continuar) {
                    $this->new_message("<a href='" . $asiento->url() . "'>Asiento de pago</a> guardado correctamente!");
                } else {
                    if ($asiento->delete()) {
                        $this->new_error_msg("¡Error en alguna de las partidas! Se ha borrado el asiento.");
                    } else {
                        $this->new_error_msg("¡Error en alguna de las partidas! Además ha sido imposible borrar el asiento.");
                    }
                }
            } else {
                $this->new_error_msg("¡Imposible guardar el asiento!");
            }
        }
    }

    /**
     * Copia un asiento,.
     */
    private function copiar_asiento()
    {
        $copia = $this->asiento->get($_GET['copy']);
        if ($copia) {
            $this->asiento->concepto = $copia->concepto;

            foreach ($copia->get_partidas() as $part) {
                $subc = $this->subcuenta->get($part->idsubcuenta);
                if ($subc) {
                    $part->desc_subcuenta = $subc->descripcion;
                    $part->saldo = $subc->saldo;
                } else {
                    $part->desc_subcuenta = '';
                    $part->saldo = 0;
                }

                $this->lineas[] = $part;
            }

            $this->new_advice('Copiando asiento ' . $copia->numero . '. Pulsa <b>guardar</b> para terminar.');
        } else {
            $this->new_error_msg('Asiento no encontrado.');
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

    /**
     * Comprueba que los datos contables sean correctos.
     */
    private function check_datos_contables()
    {
        $eje = $this->ejercicio->get_by_fecha($this->today());
        if ($eje) {
            $ok = false;
            foreach ($this->subcuenta->all_from_ejercicio($eje->codejercicio, true, 5) as $subc) {
                $ok = true;
                break;
            }

            if (!$ok) {
                $this->new_error_msg('No se encuentran subcuentas para el ejercicio ' . $eje->nombre
                    . ' ¿<a href="' . $eje->url() . '">Has importado los datos de contabilidad</a>?');
            }
        } else {
            $this->new_error_msg('No se encuentra ningún ejercicio abierto para la fecha ' . $this->today());
        }
    }
}
