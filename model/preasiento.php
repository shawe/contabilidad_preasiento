<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of preasiento
 *
 * @author Rafa
 */
class preasiento extends fs_model
{
    public $num_pre;
    public $Nombre;
    public $Concepto;
    public $Partidas;
    public $Variables;
    
    public function __construct($t = FALSE)
    {
        parent::__construct('co_preasientos','plugins/contabilidad_preasiento/');
        if (gettype($t) == 'array')
        {
            $this->num_pre = $t['num_pre'];
            $this->Nombre = $t['Nombre'];
            $this->Concepto = $t['Concepto'];
            $this->Partidas = $t['Partidas'];
            if (isset($t['Variables']))
                $this->Variables = $t['Variables'];
            else
                $this->Variables = array();
        }
        else if (gettype($t) == 'string')
        {
            $this->num_pre = $t;
            $this->Nombre = "";
            $this->Concepto = "";
            $this->Partidas = array();
            $this->Variables = array();
            
        }
        else
        {
            $this->num_pre = NULL;
            $this->Nombre = "";
            $this->Concepto = "";
            $this->Partidas = array();
            $this->Variables = array();
        }
    }
    
    protected function install() 
    {
        return '';
    }
    
    public function exists() 
    {
        if (is_null($this->num_pre))
            return FALSE;
        $Resultado = $this->db->select("SELECT * FROM co_preasientos WHERE num_pre = "
                . $this->var2str($this->num_pre) . ' AND id_part = "0";');
        if ($Resultado == FALSE)
            return FALSE;
        return TRUE;
    }
    
    public function save()
    {
        if ( $this->exists() )    
            $this->delete(); //No podemos update, porque no sabemos cómo ha cambiado cada partida
        //AHORA INSERT
        if ($this->num_pre == 0)
        { //Es nuevo
        $Resultado = $this->db->select("SELECT num_pre FROM co_preasientos ORDER BY num_pre DESC LIMIT 1;");
        if ( $Resultado )
            $this->num_pre = $Resultado[0]['num_pre'] + 1;
        else
            $this->num_pre = 1;
        }
        $sql = 'INSERT INTO co_preasientos (num_pre,id_part, subcuenta, debe) VALUES ('.
                $this->var2str($this->num_pre) . ', "0", ' . $this->var2str($this->Concepto) . ', ' .
                $this->var2str($this->Nombre) . ');';
        if ( $this->db->exec($sql) )
        {
            for ($z=1; $z<count($this->Partidas); $z++) //Las partidas se guardan desde 1
            {
                $sql = 'INSERT INTO co_preasientos (num_pre,id_part, subcuenta, debe, haber) VALUES ('.
                    $this->var2str($this->num_pre) . ', '. $z . ', ' . $this->var2str($this->Partidas[$z][0]).', ' .
                    $this->var2str($this->Partidas[$z][1]).', ' . $this->var2str($this->Partidas[$z][2]).');';
                if ( ! $this->db->exec($sql) )
                    return FALSE;
            }
            for ($z=0; $z<count($this->Variables); $z++)
            {
                $sql = 'INSERT INTO co_preasientos (num_pre,id_part, subcuenta, debe) VALUES ('.
                    $this->var2str($this->num_pre) . ', '. $this->var2str($this->Variables[$z][0]).', ' .
                    $this->var2str($this->Variables[$z][1]) . ', ' . $this->var2str($this->Variables[$z][2]) . ');';
                if ( ! $this->db->exec($sql) )
                    return FALSE;
            }
        return TRUE;    
        }
    }
    
    public function delete() 
    {
        $data = $this->db->select ('SELECT id FROM co_preasientos WHERE num_pre = ' . $this->var2str($this->num_pre) . ';');
        foreach($data as $d)
        {
        if ( ! $this->db->exec('DELETE FROM co_preasientos WHERE id = ' . $d['id'] . ';') )
           return FALSE;
        }
        return TRUE;
    }

    public function all()
    {
        $lista = array();
        $data = $this->db->select ('SELECT * FROM co_preasientos ORDER BY num_pre,id_part;');
        $np = 0;
        $Nombre = "";
        $Concepto = "";
        $Partidas = array();
        $Variables = array();
        foreach($data as $d)
        {
            if ($np != $d['num_pre'])
            { //Nuevo preasiento
                if ($np != 0)
                {
                    $Preasiento = new preasiento();
                    $Preasiento->num_pre = $np;
                    $Preasiento->Nombre = $Nombre;
                    $Preasiento->Concepto = $Concepto;
                    $Preasiento->Partidas = $Partidas;
                    $Preasiento->Variables = $Variables;
                    $lista[] = $Preasiento;
                }
                $np = $d['num_pre'];
                $Partidas = array();
                $Variables = array();
            }
            if ($d['id_part'] === "0")
            {
                $Nombre = $d['debe'];
                $Concepto = $d['subcuenta'];
            }
            elseif ( is_numeric($d['id_part']) )
                $Partidas[] = [$d['id_part'], $d['subcuenta'], $d['debe'], $d['haber']];
            else
                $Variables[] = [$d['id_part'], $d['subcuenta'], $d['debe']];
        }
        if ($np!=0)
        {
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
