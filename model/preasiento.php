<?php
/**
 * This file is part of contabilidad_preasiento
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
 * Description of preasiento
 *
 * @author Rafael Del Pozo Barajas
 */
class preasiento extends fs_model
{
    /**
     * TODO
     *
     * @var
     */
    public $num_pre;

    /**
     * TODO
     *
     * @var
     */
    public $Nombre;

    /**
     * TODO
     *
     * @var
     */
    public $Concepto;

    /**
     * TODO
     *
     * @var
     */
    public $Partidas;

    /**
     * TODO
     *
     * @var
     */
    public $Variables;

    /**
     * Constructor de la clase
     *
     * @param array|string|bool $t
     */
    public function __construct($t = false)
    {
        parent::__construct('co_preasientos', 'plugins/contabilidad_preasiento/');
        if (is_array($t)) {
            $this->num_pre = $t['num_pre'];
            $this->Nombre = $t['Nombre'];
            $this->Concepto = $t['Concepto'];
            $this->Partidas = $t['Partidas'];
            $this->Variables = [];
            if (isset($t['Variables'])) {
                $this->Variables = $t['Variables'];
            }
        } elseif (is_string($t)) {
            $this->num_pre = $t;
            $this->Nombre = '';
            $this->Concepto = '';
            $this->Partidas = [];
            $this->Variables = [];
        } else {
            $this->num_pre = null;
            $this->Nombre = '';
            $this->Concepto = '';
            $this->Partidas = [];
            $this->Variables = [];
        }
    }

    /**
     * Permite añadir datos por defecto al utilizar el modelo
     *
     * @return string
     */
    protected function install()
    {
        return '';
    }

    /**
     * Devuelve True si existe, sino devuelve False
     *
     * @return boolean
     */
    public function exists()
    {
        if (null === $this->num_pre) {
            return false;
        }
        $sql = 'SELECT * FROM co_preasientos WHERE num_pre = ' . $this->var2str($this->num_pre) . ' && id_part = "0";';
        $Resultado = $this->db->select($sql);
        return !($Resultado === false);
    }

    /**
     * Guarda sino existe o inserta los datos,
     * devuelve true si se ha podido guardar y sino false.
     *
     * @return boolean
     */
    public function save()
    {
        if ($this->exists()) {
            $this->delete();
        } //No podemos update, porque no sabemos cómo ha cambiado cada partida

        //AHORA INSERT
        if ($this->num_pre == 0) { //Es nuevo
            $sql1 = 'SELECT num_pre FROM co_preasientos ORDER BY num_pre DESC LIMIT 1;';
            $Resultado = $this->db->select($sql1);
            $this->num_pre = 1;
            if ($Resultado) {
                $this->num_pre = $Resultado[0]['num_pre'] + 1;
            }
        }
        $sql2 = 'INSERT INTO co_preasientos (num_pre,id_part, subcuenta, debe) VALUES ('
            . $this->var2str($this->num_pre) . ', "0", ' . $this->var2str($this->Concepto) . ', '
            . $this->var2str($this->Nombre) . ');';
        if ($this->db->exec($sql2)) {
            $cantPartidas = count($this->Partidas);
            for ($z = 1; $z < $cantPartidas; $z++) { //Las partidas se guardan desde 1
                $sql3 = 'INSERT INTO co_preasientos (num_pre,id_part, subcuenta, debe, haber) VALUES (' .
                    $this->var2str($this->num_pre) . ', ' . $z . ', ' . $this->var2str($this->Partidas[$z][0]) . ', ' .
                    $this->var2str($this->Partidas[$z][1]) . ', ' . $this->var2str($this->Partidas[$z][2]) . ');';
                if (!$this->db->exec($sql3)) {
                    return false;
                }
            }
            $cantVariables = count($this->Variables);
            for ($z = 0; $z < $cantVariables; $z++) {
                $sql4 = 'INSERT INTO co_preasientos (num_pre,id_part, subcuenta, debe) VALUES (' .
                    $this->var2str($this->num_pre) . ', ' . $this->var2str($this->Variables[$z][0]) . ', ' .
                    $this->var2str($this->Variables[$z][1]) . ', ' . $this->var2str($this->Variables[$z][2]) . ');';
                if (!$this->db->exec($sql4)) {
                    return false;
                }
            }
            return true;
        }
        /// Falta este return si no ha entrado al if anterior
        // return false;
    }

    /**
     * Elimina de la tabla el registro.
     *
     * @return boolean
     */
    public function delete()
    {
        $sql = 'SELECT id FROM co_preasientos WHERE num_pre = ' . $this->var2str($this->num_pre) . ';';
        $data = $this->db->select($sql);
        foreach ($data as $d) {
            $sql = 'DELETE FROM co_preasientos WHERE id = ' . $d['id'] . ';';
            if (!$this->db->exec($sql)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Devuelve un listado de todos los preasientos.
     *
     * @return self[]
     */
    public function all()
    {
        $lista = [];
        $sql = 'SELECT * FROM co_preasientos ORDER BY num_pre,id_part;';
        $data = $this->db->select($sql);
        $np = 0;
        $Nombre = '';
        $Concepto = '';
        $Partidas = [];
        $Variables = [];
        foreach ($data as $d) {
            if ($np != $d['num_pre']) { //Nuevo preasiento
                if ($np != 0) {
                    $Preasiento = new preasiento();
                    $Preasiento->num_pre = $np;
                    $Preasiento->Nombre = $Nombre;
                    $Preasiento->Concepto = $Concepto;
                    $Preasiento->Partidas = $Partidas;
                    $Preasiento->Variables = $Variables;
                    $lista[] = $Preasiento;
                }
                $np = $d['num_pre'];
                $Partidas = [];
                $Variables = [];
            }
            if ($d['id_part'] === '0') {
                $Nombre = $d['debe'];
                $Concepto = $d['subcuenta'];
            } elseif (is_numeric($d['id_part'])) {
                $Partidas[] = [$d['id_part'], $d['subcuenta'], $d['debe'], $d['haber']];
            } else {
                $Variables[] = [$d['id_part'], $d['subcuenta'], $d['debe']];
            }
        }
        if ($np != 0) {
            $Preasiento = new preasiento();
            $Preasiento->num_pre = $np;
            $Preasiento->Nombre = $Nombre;
            $Preasiento->Concepto = $Concepto;
            $Preasiento->Partidas = $Partidas;
            $Preasiento->Variables = $Variables;
            $lista[] = $Preasiento;
        }
        return $lista;
    }
}
